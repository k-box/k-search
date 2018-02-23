<?php

namespace App\Service;

use App\Entity\SolrEntity;
use App\Entity\SolrEntityData;
use App\Exception\BadRequestException;
use App\Exception\DataDownloadErrorException;
use App\Exception\InternalSearchException;
use App\Exception\SolrEntityNotFoundException;
use App\Exception\SolrExtractionException;
use App\Model\Data\Data;
use App\Model\Data\Search\Aggregation;
use App\Model\Data\Search\SearchParams;
use App\Model\Data\Search\SearchResults;
use App\Queue\Message\UUIDMessage;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Solarium\QueryType\Select\Query\Component\Facet\Field;
use Solarium\QueryType\Select\Query\Query;

class DataService
{
    private const SEARCH_USER_FILTER_KEY = 'user-filter';
    private const SEARCH_ENTITY_TYPE_KEY = 'entity-type';
    private const SEARCH_DATA_STATUS_KEY = 'data-status';
    private const SEARCH_USER_FILTER_TAG = 'user-filter';

    /**
     * @var SolrService
     */
    private $solrService;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DataDownloaderService
     */
    private $dataDownloaderService;

    /**
     * @var string[]
     */
    private $indexableContentTypes = [];

    public function __construct(
        QueueService $queueService,
        SolrService $solrService,
        DataDownloaderService $downloaderService,
        LoggerInterface $logger,
        array $indexableContentTypes = [])
    {
        $this->solrService = $solrService;
        $this->queueService = $queueService;
        $this->logger = $logger;
        $this->dataDownloaderService = $downloaderService;
        $this->indexableContentTypes = $indexableContentTypes;
    }

    /**
     * Delete a data entity from the index, given its UUID.
     *
     * @param string $uuid The Data UUID
     *
     * @throws InternalSearchException
     *
     * @return bool True when the data has been deleted, false otherwise
     */
    public function deleteData(string $uuid): bool
    {
        $indexDeleted = $this->solrService->delete(SolrEntityData::getEntityType(), $uuid);

        if ($indexDeleted) {
            // The following will not throw any exception in case of failure
            $this->dataDownloaderService->removeDownloadedDataFile($uuid);
        }

        return $indexDeleted;
    }

    /**
     * Retrieves the Data from the index, given its UUID.
     *
     * @param string      $uuid   The data UUID
     * @param string|null $status Filter the data to retrieve by the following status
     *
     * @throws SolrEntityNotFoundException
     *
     * @return Data
     */
    public function getData(string $uuid, ?string $status = null): Data
    {
        $entityType = SolrEntityData::getEntityType();

        $filterQueries = [
            $this->solrService->buildFilterQuery(SolrEntityData::FIELD_ENTITY_ID, $uuid, 'id'),
        ];

        if ($status) {
            $filterQueries[] = $this->solrService->buildFilterQuery(SolrEntityData::FIELD_STATUS, $status, 'status');
        }

        $resultSet = $this->solrService->getByFilter($entityType, SolrEntityData::class, $filterQueries, 1, 0);

        if (!$resultSet || 1 !== $resultSet->getNumFound()) {
            throw new SolrEntityNotFoundException(sprintf('Resource %s::%s not found!', $entityType, $uuid));
        }

        // Building the required SolrEntity object from the result document
        $solrEntityData = new SolrEntityData($uuid, $resultSet->getIterator()[0]);

        return $solrEntityData->buildModel();
    }

    /**
     * Adds the specific data to the index.
     * If the textualContents are provided, the indexing is performed without queuing.
     *
     * @param Data        $data            The Data object
     * @param null|string $textualContents The textual contents to be indexed
     *
     * @throws BadRequestException        if the data provided can not be indexed
     * @throws DataDownloadErrorException
     *
     * @return bool
     */
    public function addData(Data $data, ?string $textualContents = null): bool
    {
        $this->dataCleanup($data);

        $dataEntity = null;
        $enqueue = true;

        if (!empty($textualContents)) {
            // If the textual contents are provided, we straight index them
            $data->status = $data->status ?? Data::STATUS_OK;
            $dataEntity = SolrEntityData::buildFromModel($data);
            $dataEntity->addTextualContents($textualContents);
            $enqueue = false;
        } else {
            $this->ensureDataIsIndexable($data);
            // Otherwise, we queue the data to be indexed later.
            $data->status = Data::STATUS_QUEUED;
            $dataEntity = SolrEntityData::buildFromModel($data);
        }

        $this->logger->info('Adding Data object to the index, enqueue={enqueue}, id={uuid}', [
            'uuid' => $data->uuid,
            'enqueue' => $enqueue,
        ]);

        $this->solrService->add($dataEntity);

        if ($enqueue) {
            // We enqueue the data to be processed later, only if we were able to add it to the Index!
            $this->queueService->enqueueMessage(
                QueueService::DATA_PROCESS_QUEUE,
                new UUIDMessage($data->uuid)
            );
        }

        return true;
    }

    /**
     * Adds the specific data to the index, by extracting the text from the given file.
     *
     * @param Data         $data     The Data object
     * @param \SplFileInfo $fileInfo The file to extract the textual contents from
     *
     * @throws SolrExtractionException
     * @throws InternalSearchException
     *
     * @return bool
     */
    public function addDataWithFileExtraction(Data $data, \SplFileInfo $fileInfo): bool
    {
        $this->dataCleanup($data);
        $data->status = Data::STATUS_OK;
        $dataEntity = SolrEntityData::buildFromModel($data);

        return $this->solrService->addWithTextExtraction($dataEntity, $fileInfo);
    }

    /**
     * Executes a search for a set of Data entities with the specified params.
     *
     * @param SearchParams $searchParams The Search parameters
     * @param string       $version      The API version, useful for migration and BC data handling
     *
     * @throws BadRequestException
     *
     * @return SearchResults
     */
    public function searchData(SearchParams $searchParams, string $version): SearchResults
    {
        $this->handleSearchParamVersion($searchParams, $version);

        // Building the search query
        $query = $this->solrService->buildSelectQueryByEntityType(SolrEntityData::class, self::SEARCH_ENTITY_TYPE_KEY);

        // Find only Data that is correctly indexed
        $query->addFilterQuery($this->solrService->buildFilterQuery(
            SolrEntityData::FIELD_STATUS,
            Data::STATUS_OK,
            self::SEARCH_DATA_STATUS_KEY
        ));

        // Setting limit and offset
        $query->setRows($searchParams->limit);
        $query->setStart($searchParams->offset);

        // Setting the search terms
        $query->setQuery($searchParams->search);

        // Add full-text matching configuration
        $this->addFullTextMatching($query);

        // Adding aggregations (aka Solr Facets)
        if ($facets = $this->buildSearchFacets($searchParams)) {
            $query->getFacetSet()->addFacets($facets);
        }

        // Adding sorting
        if ($sorts = $this->buildSearchSorts($searchParams)) {
            $query->setSorts($sorts);
        }

        // Adding search filters
        if ($searchParams->filters) {
            $filterQuery = $this->solrService->buildFilterFromString(
                $searchParams->filters,
                SolrEntityData::getFilterFields(),
                self::SEARCH_USER_FILTER_KEY
            );
            $filterQuery->addTag(self::SEARCH_USER_FILTER_TAG);

            $query->addFilterQuery($filterQuery);
        }

        $query->setOmitHeader(false);
        $queryResult = $this->solrService->executeSelectQuery($query);

        $searchResults = new SearchResults($searchParams);
        $searchResults->queryTime = $queryResult->getQueryTime();
        $searchResults->totalMatches = $queryResult->getNumFound();

        $searchResults->items = $this->solrService->buildSolrModelsFromResult($queryResult, SolrEntityData::class);

        $searchResults->aggregations = $this->solrService->buildAggregationsFromResult($queryResult);

        return $searchResults;
    }

    /**
     * Checks if the given Data is indexable, without providing the textual-contents directly.
     *
     * @param Data $data
     *
     * @throws BadRequestException
     * @throws DataDownloadErrorException
     */
    public function ensureDataIsIndexable(Data $data)
    {
        $mimeType = $this->dataDownloaderService->getDataFileMimetype($data);

        if (!$mimeType) {
            throw new BadRequestException([
                sprintf('The given Data could not be indexed. Unable to guess the mimetype for %s', $data->url),
            ]);
        }

        if (!in_array($mimeType, $this->indexableContentTypes, true)) {
            throw new BadRequestException([
                sprintf('The given Data could not be indexed: the mime-type %s is not supported.', $mimeType),
            ]);
        }
    }

    /**
     * Cleanup the given data, updates the missing fields.
     *
     * @param Data $data
     */
    protected function dataCleanup(Data $data)
    {
        if (!$data->properties->updatedAt) {
            $data->properties->updatedAt = new \DateTime('now', new DateTimeZone('UTC'));
        }
    }

    /**
     * Returns the list of Facets enabled in the given the SearchParams.
     *
     * @param SearchParams $searchParams
     *
     * @throws BadRequestException if any of the facet is not valid
     *
     * @return Field[]
     */
    private function buildSearchFacets(SearchParams $searchParams): array
    {
        $facets = [];
        $availableAggregations = SolrEntityData::getAggregationFields();

        foreach ($searchParams->aggregations as $property => $aggregation) {
            if (!array_key_exists($property, $availableAggregations)) {
                throw new BadRequestException([
                    'aggregations' => sprintf('Aggregation on property "%s" is not available', $property),
                ]);
            }

            $fieldName = $availableAggregations[$property];
            $facet = $this->solrService->buildFacet($fieldName, $aggregation->limit, $aggregation->minCount, $property);

            if (!$aggregation->countsFiltered) {
                $facet->setExcludes([self::SEARCH_USER_FILTER_TAG]);
            }
            $facets[] = $facet;
        }

        return $facets;
    }

    /**
     * Returns the search sorting configuration.
     *
     * @param SearchParams $searchParams
     *
     * @return string[] The sorting fields (as field => order hashmap)
     */
    private function buildSearchSorts(SearchParams $searchParams): array
    {
        $sorts = [];
        $sortingFields = SolrEntityData::getSortingFields();
        foreach ($searchParams->sort as $sortParam) {
            $sorts[$sortingFields[$sortParam->field]] = $sortParam->order;
        }

        return $sorts;
    }

    /**
     * Handle default version changes for SearchParams.
     *
     * @param SearchParams $searchParams
     * @param string       $version
     */
    private function handleSearchParamVersion(SearchParams $searchParams, string $version): void
    {
        // Handle AggregationMinCount for version < 3.2
        if (version_compare($version, '3.2', '<')) {
            foreach ($searchParams->aggregations as $aggregation) {
                $aggregation->minCount = 0;
            }
        }
    }

    private function addFullTextMatching(Query $query)
    {
        $edisMax = $query->getEDisMax();
        $edisMax->setQueryFields(implode(' ', SolrEntityData::getTextSearchFields()));
        $edisMax->setUserFields('-*');

        // Handle phrase matching
        $edisMax->setPhraseFields(implode(' ', SolrEntityData::getTextPhraseSearchFields()));
        $edisMax->setPhraseSlop('2');
    }
}
