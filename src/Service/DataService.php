<?php

namespace App\Service;

use App\Entity\SolrEntityData;
use App\Exception\BadRequestException;
use App\Exception\DataDownloadErrorException;
use App\Exception\FilterQuery\FilterQueryException;
use App\Exception\FilterQuery\InvalidGeoJsonFilterException;
use App\Exception\InternalSearchException;
use App\Exception\OutdatedDataRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\Exception\SolrExtractionException;
use App\GeoJson\Exception\GeoJsonException;
use App\GeoJson\Model\Polygon;
use App\GeoJson\ModelFactory;
use App\Helper\DateHelper;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Model\Data\Search\SearchParams;
use App\Model\Data\Search\SearchResults;
use App\Queue\Message\DataDownloadMessage;
use Psr\Log\LoggerInterface;
use Solarium\Component\Facet\Field;
use Solarium\QueryType\Select\Query\Query;
use Symfony\Component\Messenger\MessageBusInterface;

class DataService
{
    private const SEARCH_USER_FILTER_KEY = 'user-filter';
    private const SEARCH_ENTITY_TYPE_KEY = 'entity-type';
    private const SEARCH_DATA_STATUS_KEY = 'data-status';
    private const SEARCH_USER_FILTER_TAG = 'user-filter';
    private const SEARCH_GEO_FILTER_KEY = 'geo-location-filter';

    /**
     * @var SolrService
     */
    private $solrService;

    /**
     * @var DataStatusService
     */
    private $dataStatusService;

    /**
     * @var DataProcessingService
     */
    private $dataProcessingService;

    /**
     * @var DataDownloader
     */
    private $dataDownloader;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var string[]
     */
    private $indexableContentTypes;

    /**
     * @var bool
     */
    private $retainDataContents;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DataProcessingService $processingService,
        DataStatusService $dataStatusService,
        SolrService $solrService,
        DataDownloader $downloaderService,
        MessageBusInterface $messageBus,
        array $indexableContentTypes,
        bool $retainDataContents,
        LoggerInterface $logger
    ) {
        $this->dataProcessingService = $processingService;
        $this->solrService = $solrService;
        $this->dataDownloader = $downloaderService;
        $this->messageBus = $messageBus;
        $this->indexableContentTypes = $indexableContentTypes;
        $this->retainDataContents = $retainDataContents;
        $this->logger = $logger;
        $this->dataStatusService = $dataStatusService;
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
        $this->logger->info('Deleting data from index, uuid={uuid}', [
            'uuid' => $uuid,
        ]);

        $deleted = $this->solrService->delete(SolrEntityData::getEntityType(), $uuid);

        if ($deleted) {
            // The following will not throw any exception in case of failure
            $this->dataDownloader->removeDownloadedDataFile($uuid);
            $this->dataDownloader->removeStoredTextualContents($uuid);
            $this->dataProcessingService->deleteProcessingStatus($uuid);
        }

        return $deleted;
    }

    /**
     * Retrieves the Data from the index, given its UUID.
     *
     * @param string $uuid The data UUID
     *
     * @throws SolrEntityNotFoundException
     */
    public function getData(string $uuid): Data
    {
        $entityType = SolrEntityData::getEntityType();

        $filterQueries = [
            $this->solrService->buildFilterQuery(SolrEntityData::FIELD_ENTITY_ID, $uuid, 'id'),
        ];

        $resultSet = $this->solrService->getByFilter($entityType, SolrEntityData::class, $filterQueries, 1, 0);

        if (1 !== $resultSet->getNumFound()) {
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
     * @param Data        $data            The Data model
     * @param null|string $textualContents The textual contents to be indexed
     * @param string|null $requestId       The requestId initiating the request
     *
     * @throws BadRequestException
     * @throws DataDownloadErrorException
     * @throws OutdatedDataRequestException
     *
     * @return bool True if the data was correctly handled
     */
    public function addData(Data $data, ?string $textualContents = null, string $requestId = null): bool
    {
        if ($this->dataStatusService->isDataNewer($data->uuid, DateHelper::createUtcDate())) {
            throw OutdatedDataRequestException::fromRequestId($requestId ?? '');
        }

        $this->dataCleanup($data, $requestId);

        if (null !== $textualContents) {
            $textualContents = trim($textualContents);
            $textualContents = $textualContents ?? null;
        }

        if ($textualContents) {
            return $this->addDataToIndex($data, $textualContents);
        }

        $data->status = DataStatus::STATUS_QUEUED_OK;
        // Ensure the data is indexable
        $this->ensureDataIsIndexable($data);
        $this->logger->info('Adding Data object to download processing queue, id={uuid}', [
            'uuid' => $data->uuid,
        ]);

        $this->dataProcessingService->addDataForProcessing($data);

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
     */
    public function addDataWithFileExtraction(Data $data, \SplFileInfo $fileInfo): bool
    {
        $this->dataCleanup($data);
        $data->status = DataStatus::STATUS_INDEX_OK;
        $dataEntity = SolrEntityData::buildFromModel($data);

        $this->logger->info(
            'Adding Data object to the index with text extraction, uuid={uuid}, filename={filename}, file-size={size}',
            [
                'uuid' => $data->uuid,
                'filename' => $fileInfo->getFilename(),
                'size' => $fileInfo->getSize(),
            ]
        );

        $result = $this->solrService->addWithTextExtraction($dataEntity, $fileInfo);

        // Remove stale stored textual-contents
        $this->dataDownloader->removeStoredTextualContents($data->uuid);

        if (!$this->retainDataContents) {
            // Downloaded files must be removed after indexing is successful
            $this->dataDownloader->removeDownloadedDataFile($data->uuid);
        }

        return $result;
    }

    /**
     * Executes a search for a set of Data entities with the specified params.
     *
     * @param SearchParams $searchParams The Search parameters
     * @param string       $version      The API version, useful for migration and BC data handling
     *
     * @throws BadRequestException
     * @throws FilterQueryException
     */
    public function searchData(SearchParams $searchParams, string $version): SearchResults
    {
        $this->logger->info(
            'Executing Data search, version={version}',
            [
                'params' => $searchParams,
                'version' => $version,
            ]
        );

        $this->handleSearchParamVersion($searchParams, $version);

        // Building the search query
        $query = $this->solrService->buildSelectQueryByEntityType(SolrEntityData::class, self::SEARCH_ENTITY_TYPE_KEY);

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

        if ($searchParams->geoLocationFilter) {
            try {
                $polygon = ModelFactory::buildFromJson($searchParams->geoLocationFilter->boundingBox);
            } catch (GeoJsonException $e) {
                throw new InvalidGeoJsonFilterException($e->getMessage());
            }
            if (!$polygon instanceof Polygon) {
                throw new InvalidGeoJsonFilterException('Unsupported Type');
            }

            $geoFilterQuery = $this->solrService->buildPolygonIntersectFilter(SolrEntityData::FIELD_GEO_LOCATION, $polygon);
            $geoFilterQuery->setKey(self::SEARCH_GEO_FILTER_KEY);
            $query->addFilterQuery($geoFilterQuery);
        }

        // Keep the header to get the Solr query time
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
     *
     * @throws BadRequestException
     * @throws DataDownloadErrorException
     */
    public function ensureDataIsIndexable(Data $data): void
    {
        $mimeType = $this->dataDownloader->getDataFileMimetype($data);

        if (!$mimeType) {
            throw new BadRequestException(
                [
                    sprintf('The given Data could not be indexed. Unable to guess the mimetype for %s', $data->url),
                ]
            );
        }

        if (!\in_array($mimeType, $this->indexableContentTypes, true)) {
            throw new BadRequestException(
                [
                    sprintf('The given Data could not be indexed: the mime-type %s is not supported.', $mimeType),
                ]
            );
        }
    }

    /**
     * Cleanup the given data, updates the missing fields.
     */
    private function dataCleanup(Data $data, string $requestId = null): void
    {
        if (!$data->requestId && $requestId) {
            $data->requestId = $requestId;
        }

        if (!$data->properties->updatedAt) {
            $data->properties->updatedAt = DateHelper::createUtcDate();
        }
        if (!$data->updatedAt) {
            $data->updatedAt = DateHelper::createUtcDate();
        }
    }

    private function addDataToIndex(Data $data, string $textualContents): bool
    {
        $this->logger->info('Adding Data object to the index directly, id={uuid}', [
            'uuid' => $data->uuid,
        ]);
        $data->status = $data->status ?? DataStatus::STATUS_INDEX_OK;
        $dataEntity = SolrEntityData::buildFromModel($data);
        $dataEntity->addTextualContents($textualContents);

        $this->solrService->add($dataEntity);

        // Store the textualContents
        if ($this->retainDataContents) {
            $this->dataDownloader->storeDataTextualContents($data->uuid, $textualContents);

            // Dispatch message for file download, if data type is not Video
            if (Data::DATA_TYPE_VIDEO !== $data->type) {
                $this->messageBus->dispatch(new DataDownloadMessage($data->uuid, $data->requestId));
            }
        }

        return true;
    }

    /**
     * Returns the list of Facets enabled in the given the SearchParams.
     *
     * @throws BadRequestException if any of the facet is not valid
     *
     * @return Field[]
     */
    private function buildSearchFacets(SearchParams $searchParams): array
    {
        if (!$searchParams->aggregations) {
            return [];
        }

        $availableAggregations = SolrEntityData::getAggregationFields();
        $facets = [];
        foreach ($searchParams->aggregations as $property => $aggregation) {
            if (!array_key_exists($property, $availableAggregations)) {
                throw new BadRequestException(
                    [
                        'aggregations' => sprintf('Aggregation on property "%s" is not available', $property),
                    ]
                );
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
     *
     * @return string[] The sorting fields (as field => order hashmap)
     */
    private function buildSearchSorts(SearchParams $searchParams): array
    {
        if (!$searchParams->sort) {
            return [];
        }

        $sortingFields = SolrEntityData::getSortingFields();
        $sorts = [];
        foreach ($searchParams->sort as $sortParam) {
            $sorts[$sortingFields[$sortParam->field]] = $sortParam->order;
        }

        return $sorts;
    }

    /**
     * Handle default version changes for SearchParams.
     */
    private function handleSearchParamVersion(SearchParams $searchParams, string $version): void
    {
        if (!$searchParams->aggregations) {
            return;
        }

        // Handle AggregationMinCount for version < 3.2
        if (version_compare($version, '3.2', '<')) {
            foreach ($searchParams->aggregations as $aggregation) {
                $aggregation->minCount = 0;
            }
        }
    }

    private function addFullTextMatching(Query $query): void
    {
        $edisMax = $query->getEDisMax();
        $edisMax->setQueryFields(implode(' ', SolrEntityData::getTextSearchFields()));
        $edisMax->setUserFields('-*');

        // Handle phrase matching
        $edisMax->setPhraseFields(implode(' ', SolrEntityData::getTextPhraseSearchFields()));
        $edisMax->setPhraseSlop('2');
    }
}
