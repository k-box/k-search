<?php

namespace App\Service;

use App\Entity\AbstractSolrEntity;
use App\Entity\SolrEntity;
use App\Entity\SolrEntityExtractText;
use App\Exception\BadRequestException;
use App\Exception\InternalSearchException;
use App\Exception\SolrEntityNotFoundException;
use App\Exception\SolrExtractionException;
use App\Helper\SolrHelper;
use App\Model\Data\Search\Aggregation;
use App\Model\Data\Search\AggregationResult;
use Solarium\Client;
use Solarium\Exception\ExceptionInterface;
use Solarium\QueryType\Select\Query\Component\Facet\Field;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;

class SolrService
{
    /**
     * @var Client
     */
    private $solrClient;

    public function __construct(Client $solrClient)
    {
        $this->solrClient = $solrClient;
    }

    public function add(AbstractSolrEntity $solrEntity)
    {
        $update = $this->solrClient->createUpdate();
        $update->addDocument($solrEntity->getSolrUpdateDocument());
        $update->addCommit();

        return $this->solrClient->update($update);
    }

    public function getByFilter(string $entityType, string $solrEntityClass, array $filterQueries = [], int $limit = 10, int $offset = 0): Result
    {
        if (!in_array(SolrEntity::class, class_implements($solrEntityClass), true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $select = $this->solrClient->createSelect();
        $select->setRows($limit);
        $select->setStart($offset);

        $select->addFilterQuery($this->buildFilterQuery(AbstractSolrEntity::FIELD_ENTITY_TYPE, $entityType, 'type'));
        $select->addFilterQueries($filterQueries);

        $resultSet = null;
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
        if (!in_array(SolrEntity::class, class_implements($solrEntityClass), true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $idFilter = $this->buildFilterQuery(AbstractSolrEntity::FIELD_ENTITY_ID, $id, 'id');

        $resultSet = $this->getByFilter($entityType, $solrEntityClass, [$idFilter], 1, 0);

        if (!$resultSet || 1 !== $resultSet->getNumFound()) {
            throw new SolrEntityNotFoundException(sprintf('Resource %s::%s not found!', $entityType, $id));
        }

        // Building the required SolrEntity object from the result document
        return new $solrEntityClass($id, $resultSet->getIterator()[0]);
    }

    /**
     * Deletes an entity from the index given the type and the ID.
     *
     * @param string $entityType The entity type
     * @param string $id         The entity id
     *
     * @throws InternalSearchException
     *
     * @return bool
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
     *
     * @return bool
     */
    public function addWithTextExtraction(AbstractSolrEntity $entity, \SplFileInfo $fileInfo)
    {
        if (!$entity instanceof SolrEntityExtractText) {
            throw new \RuntimeException(sprintf(
                'Wrong object type for text-extracting. %s does not implement %s',
                get_class($entity),
                SolrEntityExtractText::class)
            );
        }

        $extract = $this->solrClient->createExtract();
        $extract->setFile($fileInfo->getRealPath());
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
     *
     * @return Query
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
     *
     * @return FilterQuery
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
     * @param string      $field The field name
     * @param int|null    $limit The number of facet items to return
     * @param string|null $key   The facet key
     *
     * @return Field
     */
    public function buildFacet(string $field, ?int $limit = null, ?string $key = null): Field
    {
        $facet = new Field();
        $facet->setLimit($limit);
        $facet->setField($field);
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
     * @throws BadRequestException
     *
     * @return FilterQuery
     */
    public function buildFilterFromString(string $filterString, array $propertyToFieldMapping, string $key): FilterQuery
    {
        $filter = new FilterQuery();
        $filter->setKey($key);

        $properties = SolrHelper::getModelPropertiesInFilterQuery($filterString);

        $flippedProperties = array_flip($properties);
        $invalidProperties = array_diff_key($flippedProperties, $propertyToFieldMapping);
        if (count($invalidProperties)) {
            throw new BadRequestException([
                sprintf('Invalid filter properties: %s', implode(',', $invalidProperties)),
            ]);
        }

        $filter->setQuery(SolrHelper::replacePropertyToFieldNames($filterString, array_intersect_key($propertyToFieldMapping, $flippedProperties)));

        return $filter;
    }

    public function executeSelectQuery(Query $query): Result
    {
        return $this->solrClient->select($query);
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
            $models[] = $entity->buildModel();
        }

        return $models;
    }

    /**
     * Build the aggregations output from a Result.
     *
     * @param Result $result
     *
     * @return Aggregation[]
     */
    public function buildAggregationsFromResult(Result $result): array
    {
        $aggregations = [];

        if (!$result->getFacetSet()) {
            // if facets are unset, return empty aggregations list
            return $aggregations;
        }

        foreach ($result->getFacetSet()->getFacets() as $property => $facets) {
            foreach ($facets as $value => $count) {
                $aggregations[$property][] = new AggregationResult($value, $count);
            }
        }

        return $aggregations;
    }

    /**
     * Handle an Exception thrown by Solr.
     *
     * @param \Throwable $exception
     * @param string     $additionalMessage
     *
     * @throws InternalSearchException
     */
    private function handleSolariumExceptions(\Throwable $exception, string $additionalMessage)
    {
        throw new InternalSearchException(
            $additionalMessage,
            $exception->getCode(),
            $exception
        );
    }
}
