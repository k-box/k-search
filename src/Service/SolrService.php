<?php

namespace App\Service;

use App\Entity\BaseSolrEntity;
use App\Entity\SolrEntity;
use App\Entity\SolrEntityData;
use App\Exception\InternalSearchException;
use App\Exception\ResourceNotFoundException;
use App\Helper\SearchHelper;
use App\Model\Data\SearchParams;
use PHPUnit\Util\Filter;
use Solarium\Client;
use Solarium\Exception\ExceptionInterface;
use Solarium\QueryType\Select\Query\FilterQuery;
use Symfony\Component\Finder\SplFileInfo;
use Solarium\QueryType\Select\Result\AbstractDocument;
use Solarium\QueryType\Select\Result\DocumentInterface;
use Solarium\QueryType\Select\Result\Result;

class SolrService
{
    const DATA_TEXTUAL_DYNAMIC_FIELD_NAME = 'str_ss_file_content';

    /**
     * @var Client
     */
    private $solrClient;

    public function __construct(Client $solrClient)
    {
        $this->solrClient = $solrClient;
    }

    public function add(SolrEntity $solrEntity)
    {
        $update = $this->solrClient->createUpdate();
        $update->addDocument($solrEntity->getSolrDocument());
        $update->addCommit();

        return $this->solrClient->update($update);
    }

    public function get(string $entityType, string $id, string $solrEntityClass)
    {
        if (!is_a($solrEntityClass, SolrEntity::class, true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $select = $this->solrClient->createSelect();
        $select
            ->setStart(0)
            ->setRows(1)
            ->setQuery(BaseSolrEntity::FIELD_ENTITY_ID.':"'.$id.'"');

        $filterQuery = new FilterQuery(['key' => 'entity-filter']);
        $filterQuery->setQuery(BaseSolrEntity::FIELD_ENTITY_TYPE.':"'.$entityType.'"');
        $select->addFilterQueries([$filterQuery]);

        $resultSet = null;
        try {
            $resultSet = $this->solrClient->select($select);
        } catch (ExceptionInterface $exception) {
            $this->handleSolariumExceptions(
                $exception,
                sprintf('Error while loading from Index, type=%s, id=%s', $entityType, $id)
            );
        }

        if (!$resultSet || $resultSet->count() !== 1) {
            throw new ResourceNotFoundException(sprintf('Resource %s::%s not found!', $entityType, $id));
        }

        // Building the required SolrEntity object
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
        $query = ' +'.BaseSolrEntity::FIELD_ENTITY_TYPE.':"'.$entityType.'"'.
                 ' +'.BaseSolrEntity::FIELD_ENTITY_ID.':"'.$id.'"'
        ;

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
     * @param SolrEntity  $entity   The entity to add to the index
     * @param SplFileInfo $fileInfo The file to be used to extract the contents from
     *
     * @throws \Exception
     * @throws \Throwable
     *
     * @return bool
     */
    public function addWithTextExtraction(SolrEntity $entity, \SplFileInfo $fileInfo)
    {
        $extract = $this->solrClient->createExtract();
        $extract->setFile($fileInfo->getRealPath());
        $extract->setFieldMappings(['content' => $entity->getContentsField()]);
        $extract->setDocument($entity->getSolrDocument());

        // Adding extra attributes to store the extracted data
        // $extract->setUprefix('str_sm_doc_attributes_');
        $extract->setCommit(true);

        try {
            $this->solrClient->update($extract);

            return true;
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), '.PDFParser') !== false) {
                throw new \Exception('PDF Parsing Exception', 500, $e);
            }
            throw $e;
        }
    }

    private function handleSolariumExceptions(ExceptionInterface $exception, string $additionalMessage)
    {
        throw new InternalSearchException(
            $additionalMessage,
            $exception->getCode(),
            $exception
        );
    }

    /**
     * @param SearchParams $searchParams
     * @param string $solrEntityClass
     * @return Result
     */
    public function select(SearchParams $searchParams, string $solrEntityClass): Result
    {
        if (!is_a($solrEntityClass, SolrEntity::class, true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $select = $this->solrClient->createSelect();
        $select
            ->setStart($searchParams->offset)
            ->setRows($searchParams->limit);

        $entityType = call_user_func([$solrEntityClass, 'getEntityType']);

        $select->setQuery('*%P1%*', [$searchParams->search]);

        // EDisMax fields
        $indexableFields = call_user_func([$solrEntityClass, 'getIndexableFields']);
        $edisMax = $select->getEDisMax();
        $edisMax->setQueryFields(implode(' ', $indexableFields));

        $facets = $select->getFacetSet();
        $facets->createFacetField('usage')
            ->setField(SolrEntityData::FIELD_COPYRIGHT_USAGE_SHORT);

        $availableFilters = [];
        $entityFilter = new FilterQuery(['key' => 'entity-filter']);
        $entityFilter->setQuery(sprintf('%s:%s', SolrEntity::FIELD_ENTITY_TYPE, $entityType));
        $availableFilters[] = $entityFilter;

        if (!empty($searchParams->filters)) {
            $userFilter = new FilterQuery([
                'key' => 'user-filter'
            ]);

            $userFilter->setQuery(SearchHelper::transformFieldNames($solrEntityClass, $searchParams->filters));

            $availableFilters[] = $userFilter;
        }

        $select->addFilterQueries($availableFilters);

        $facets = $select->getFacetSet();
        foreach ($searchParams->aggregations as $facetField => $aggregation) {
            $facets->createFacetField($facetField)
                ->setField(SearchHelper::transformFieldNames($solrEntityClass, $facetField));
        }

        return $this->solrClient->select($select);
    }
}
