<?php

namespace App\Service;

use App\Entity\AbstractSolrEntity;
use App\Entity\SolrEntity;
use App\Entity\SolrEntityExtractText;
use App\Exception\FilterQuery\FilterQueryException;
use App\Exception\FilterQuery\InvalidQueryException;
use App\Exception\InternalSearchException;
use App\Exception\SolrEntityNotFoundException;
use App\Exception\SolrExtractionException;
use App\GeoJson\Model\Polygon;
use App\GeoJson\WGS84Lib;
use App\Helper\SolrHelper;
use App\Model\Data\Search\Aggregation;
use App\Model\Data\Search\AggregationResult;
use Psr\Log\LoggerInterface;
use Solarium\Client;
use Solarium\Component\Facet\Field;
use Solarium\Exception\ExceptionInterface;
use Solarium\Exception\HttpException;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;

class SolrService
{
    /**
     * @var Client
     */
    private $solrClient;

    /**
     * @var QueryService
     */
    private $queryService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KlinkService
     */
    private $klinks;

    public function __construct(Client $solrClient, QueryService $queryService, LoggerInterface $logger, KlinkService $klinks)
    {
        $this->solrClient = $solrClient;
        $this->queryService = $queryService;

        // Enable using POST http method for big requests
        $this->solrClient->getPlugin('postbigrequest');
        $this->logger = $logger;
        $this->klinks = $klinks;
    }

    public function add(AbstractSolrEntity $solrEntity)
    {
        $update = $this->solrClient->createUpdate();
        $update->addDocument($solrEntity->getSolrUpdateDocument());
        $update->addCommit();

        return $this->solrClient->update($update);
    }

    public function getByFilter(string $entityType, string $solrEntityClass, array $filterQueries = [], int $limit = 10, int $offset = 0, array $fields = null): Result
    {
        if (!\in_array(SolrEntity::class, class_implements($solrEntityClass), true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $select = $this->solrClient->createSelect();
        $select->setRows($limit);
        $select->setStart($offset);

        $select->addFilterQuery($this->buildFilterQuery(AbstractSolrEntity::FIELD_ENTITY_TYPE, $entityType, 'type'));
        $select->addFilterQueries($filterQueries);

        if (\is_array($fields)) {
            $select->setFields($fields);
        }

        try {
            return $this->executeSelectQuery($select);
        } catch (ExceptionInterface | \Throwable $exception) {
            $this->handleSolariumExceptions(
                $exception,
                sprintf('Error while loading/filtering from Index, type=%s', $entityType)
            );
        }
    }

    public function getOne(string $entityType, string $solrEntityClass, string $id)
    {
        if (!\in_array(SolrEntity::class, class_implements($solrEntityClass), true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $idFilter = $this->buildFilterQuery(AbstractSolrEntity::FIELD_ENTITY_ID, $id, 'id');

        $resultSet = $this->getByFilter($entityType, $solrEntityClass, [$idFilter], 1, 0);

        if (1 !== $resultSet->getNumFound()) {
            throw new SolrEntityNotFoundException(sprintf('Resource %s::%s not found!', $entityType, $id));
        }

        // Building the required SolrEntity object from the result document
        return (new $solrEntityClass($id, $resultSet->getIterator()[0]))->setKlinkResolver($this->klinks);
    }

    /**
     * Deletes an entity from the index given the type and the ID.
     *
     * @param string $entityType The entity type
     * @param string $id         The entity id
     *
     * @throws InternalSearchException
     */
    public function delete(string $entityType, string $id): bool
    {
        $update = $this->solrClient->createUpdate();
        $helper = $update->getHelper();

        $query = sprintf('+%s:"%s" +%s:"%s"',
            AbstractSolrEntity::FIELD_ENTITY_TYPE, $entityType,
                 AbstractSolrEntity::FIELD_ENTITY_ID, $helper->escapeTerm($id)
        );

        $update->addDeleteQuery($query);
        $update->addCommit();

        try {
            $result = $this->solrClient->update($update);

            return 0 === $result->getStatus();
        } catch (ExceptionInterface $exception) {
            $this->handleSolariumExceptions(
                $exception,
                sprintf('Error while deleting from Index, type=%s, id=%s', $entityType, $id)
            );
        }
    }

    /**
     * Add an entity to the index, with text extraction from a file.
     *
     * @param AbstractSolrEntity $entity   The entity to add to the index
     * @param \SplFileInfo       $fileInfo The file to be used to extract the contents from
     *
     * @throws InternalSearchException
     * @throws SolrExtractionException
     */
    public function addWithTextExtraction(AbstractSolrEntity $entity, \SplFileInfo $fileInfo): bool
    {
        if (!$entity instanceof SolrEntityExtractText) {
            throw new \RuntimeException(sprintf(
                'Wrong object type for text-extracting. %s does not implement %s',
                \get_class($entity),
                SolrEntityExtractText::class)
            );
        }

        $realPath = $fileInfo->getRealPath();
        if (!$realPath) {
            throw new \RuntimeException('Error while extracting contents from not-existing file');
        }

        $extract = $this->solrClient->createExtract();
        $extract->setFile($realPath);
        $extract->setFieldMappings(['content' => $entity::getTextualContentsField()]);
        $extract->setDocument($entity->getSolrUpdateDocument());

        // Adding extra attributes to store the extracted data
        // $extract->setUprefix('str_sm_doc_attributes_');
        $extract->setCommit(true);

        try {
            $this->solrClient->update($extract);

            return true;
        } catch (ExceptionInterface $e) {
            if (false !== strpos($e->getMessage(), 'org.apache.tika.exception.TikaException')) {
                throw new SolrExtractionException('Document text extraction Exception', 500, $e);
            }

            throw new InternalSearchException('Error extracting textual contents from document', 500, $e);
        }
    }

    /**
     * Returns a Solr Query to return only results of the given class.
     *
     * @param string      $solrEntityClass The Entity to filter for
     * @param string|null $filterKey       The filter key for the entity-type filtering
     */
    public function buildSelectQueryByEntityType(string $solrEntityClass, ?string $filterKey = 'entity-type'): Query
    {
        if (!is_a($solrEntityClass, AbstractSolrEntity::class, true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $query = $this->solrClient->createSelect();

        /** @var AbstractSolrEntity $solrEntity */
        $solrEntity = new $solrEntityClass('');

        // Filter the returned entities to be of the given type
        $query->addFilterQuery($this->buildFilterQuery($solrEntity::FIELD_ENTITY_TYPE, $solrEntity::getEntityType(), $filterKey));

        return $query;
    }

    /**
     * Builds a filter query for the given field and value.
     *
     * @param string      $field The Solr field
     * @param string      $value The value to filter for
     * @param string|null $key   The filter key
     */
    public function buildFilterQuery(string $field, string $value, ?string $key = null): FilterQuery
    {
        $filterQuery = new FilterQuery();
        $filterQuery->setKey(SolrHelper::buildSolrKey($key ?? strtolower($field)));
        $filterQuery->setQuery(sprintf('%s:%s', $field, $value));

        return $filterQuery;
    }

    /**
     * Build a Solr facet.
     *
     * @param string      $field    The field name
     * @param int         $limit    The number of facet items to return
     * @param int         $minCount The min-count of the facet to return
     * @param string|null $key      The facet key
     */
    public function buildFacet(string $field, int $limit, int $minCount, ?string $key = null): Field
    {
        $facet = new Field();
        $facet->setLimit($limit);
        $facet->setField($field);
        $facet->setMinCount($minCount);
        $facet->setKey($key ?? SolrHelper::buildSolrKey($field));

        return $facet;
    }

    /**
     * Builds a FilterQuery from the given string and the property -> field mapping.
     *
     * @param string $filterString           The filter string
     * @param array  $propertyToFieldMapping the ['property-name' => 'solr_field_name'] mapping
     * @param string $key                    The filter key
     *
     * @throws FilterQueryException
     */
    public function buildFilterFromString(string $filterString, array $propertyToFieldMapping, string $key): FilterQuery
    {
        $filter = new FilterQuery();
        $filter->setKey($key);

        $filterQueryString = $this->queryService->getFilterQuery($filterString, $propertyToFieldMapping);
        $filter->setQuery($filterQueryString);

        return $filter;
    }

    public function executeSelectQuery(Query $query): Result
    {
        try {
            return $this->solrClient->select($query);
        } catch (ExceptionInterface $exception) {
            if ($exception instanceof HttpException && $body = $exception->getBody()) {
                $data = json_decode($body, true);

                if (400 === ($data['error']['code'] ?? null) && null !== $data['error']['msg'] ?? null) {
                    throw InvalidQueryException::fromError($data['error']['msg']);
                }
            }

            $this->handleSolariumExceptions(
                $exception,
                'Error while searching data'
            );
        }
    }

    public function buildSolrModelsFromResult(Result $result, string $solrEntityClass): array
    {
        if (!is_a($solrEntityClass, AbstractSolrEntity::class, true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity building, %s given', $solrEntityClass));
        }

        $models = [];

        foreach ($result->getDocuments() as $document) {
            /** @var AbstractSolrEntity $entity */
            $entity = new $solrEntityClass($document->{AbstractSolrEntity::FIELD_ENTITY_ID}, $document);
            $models[] = $entity->setKlinkResolver($this->klinks)->buildModel();
        }

        return $models;
    }

    /**
     * Build the aggregations output from a Result.
     *
     *
     * @return Aggregation[]
     */
    public function buildAggregationsFromResult(Result $result): array
    {
        $aggregations = [];

        $facetSet = $result->getFacetSet();
        if (!$facetSet) {
            // if facets are unset, return empty aggregations list
            return $aggregations;
        }

        /** @var \Solarium\Component\Result\Facet\Field $facet */
        foreach ($facetSet->getFacets() as $property => $facet) {
            foreach ($facet->getValues() as $value => $count) {
                $aggregations[$property][] = new AggregationResult($value, $count);
            }
        }

        return $aggregations;
    }

    public function buildPolygonIntersectFilter(string $field, Polygon $polygon): FilterQuery
    {
        $clonePolygon = clone $polygon;

        // Hanle SOLR issue with negative borders: https://issues.apache.org/jira/browse/LUCENE-8522
        foreach ($clonePolygon->getExteriorRing() as &$position) {
            if ($position->lon <= WGS84Lib::MIN_LON) {
                $position->lon = WGS84Lib::MIN_LON + 0.01;
            }
            if ($position->lat <= WGS84Lib::MIN_LAT) {
                $position->lat = WGS84Lib::MIN_LAT + 0.01;
            }
        }
        unset($position);

        $jsonPolygon = json_encode($clonePolygon->jsonSerialize());
        $q = new FilterQuery();
        $q->setQuery(sprintf('{!field f=%s}Intersects(%s)', $field, $jsonPolygon));

        return $q;
    }

    /**
     * Handle an Exception thrown by Solr.
     *
     * @throws InternalSearchException
     */
    private function handleSolariumExceptions(\Throwable $exception, string $additionalMessage)
    {
        $this->logger->critical('Got exception from Solr: {message}', [
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
        throw new InternalSearchException(
            $additionalMessage,
            $exception->getCode(),
            $exception
        );
    }
}
