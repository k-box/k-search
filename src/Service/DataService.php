<?php

namespace App\Service;

use App\Entity\SolrEntityData;
use App\Exception\BadRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\Data;
use App\Model\Data\SearchParams;
use App\Model\Data\SearchResults;
use App\Queue\Message\UUIDMessage;
use DateTimeZone;
use Psr\Log\LoggerInterface;

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
     * @return bool
     */
    public function deleteData(string $uuid)
    {
        return $this->solrService->delete(SolrEntityData::getEntityType(), $uuid);
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
     * @throws BadRequestException if the data provided can not be indexed
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
     * @param SearchParams $searchParams
     *
     * @throws BadRequestException
     *
     * @return SearchResults
     */
    public function searchData(SearchParams $searchParams): SearchResults
    {
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

        // Enabling Full-Text search
        $edisMax = $query->getEDisMax();
        $edisMax->setQueryFields(implode(' ', SolrEntityData::getTextSearchFields()));

        // Adding aggregations (aka Solr Facets)
        $facets = [];
        foreach ($searchParams->aggregations as $property => $aggregation) {
            if (!array_key_exists($property, SolrEntityData::getModelPropertyToFieldMappings())) {
                throw new BadRequestException([
                    'aggregations' => sprintf('Aggregation on property "%s" is not available', $property),
                ]);
            }

            $fieldName = SolrEntityData::getModelPropertyToFieldMappings()[$property];
            $facet = $this->solrService->buildFacet($fieldName, $aggregation->limit, $property);

            if (!$aggregation->countsFiltered) {
                $facet->setExcludes([self::SEARCH_USER_FILTER_TAG]);
            }
            $facets[] = $facet;
        }

        if ($facets) {
            $query->getFacetSet()->addFacets($facets);
        }

        // Adding search filters
        if ($searchParams->filters) {
            $filterQuery = $this->solrService->buildFilterFromString(
                $searchParams->filters,
                SolrEntityData::getModelPropertyToFieldMappings(),
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
     */
    public function ensureDataIsIndexable(Data $data)
    {
        $headers = $this->dataDownloaderService->getDataUrlHeaders($data);

        if (!$headers || !isset($headers['Content-Type'])) {
            throw new BadRequestException([
                sprintf('The given Data could not be indexed. No Content-Type returned while downloading from %s', $data->url),
            ]);
        }

        $contentType = reset($headers['Content-Type']);

        if (!in_array($contentType, $this->indexableContentTypes, true)) {
            throw new BadRequestException([
                sprintf('The given Data could not be indexed: the Content-Type %s is not supported.', $contentType),
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
}
