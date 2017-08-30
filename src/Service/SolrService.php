<?php

namespace App\Service;

use App\Entity\BaseSolrEntity;
use App\Entity\SolrEntity;
use App\Entity\SolrEntityData;
use App\Exception\InternalSearchException;
use App\Exception\ResourceNotFoundException;
use App\Model\Data\SearchParams;
use Solarium\Client;
use Solarium\QueryType\Select\Query\FilterQuery;
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

    public function add(SolrEntity $solrEntity, $dataTextualContent)
    {
        $doc = $solrEntity->getSolrDocument();

        if ($dataTextualContent) {
            /*$extract = $this->solrClient->createExtract();
            $extract->setFieldMappings(['content' => $dataTextualContent,]);
            $extract->setDocument($doc);
            $extract->setCommit(true);*/
            //@Todo: add content data for indexing with an extract query
            $doc->addField(self::DATA_TEXTUAL_DYNAMIC_FIELD_NAME, $dataTextualContent);
            //The line above is written to store the data textual content temporary until we implement it properly with an extract query
        }

        $update = $this->solrClient->createUpdate();
        $update->addDocument($doc);
        $update->addCommit();

        try {
            return $this->solrClient->update($update);
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), '.PDFParser') !== false) {
                throw new \Exception('PDF Parsing Exception', 500, $e);
            }
            throw $e;
        }
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

        $resultSet = $this->solrClient->select($select);

        if ($resultSet->count() !== 1) {
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
        } catch (\Throwable $e) {
            throw new InternalSearchException(
                sprintf('Error while deleting from Index, type=%s, id=%s', $entityType, $id),
                $e->getCode(),
                $e
            );
        }
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

        $query = sprintf('%s:"%s"', SolrEntityData::FIELD_PROPERTIES_STORED, $searchParams->search);
        $select = $this->solrClient->createSelect();
        $select->setQuery($query)
            ->setStart($searchParams->offset)
            ->setRows($searchParams->limit);

        $entityType = call_user_func([$solrEntityClass, 'getEntityType']);

        $filterQuery = new FilterQuery(['key' => 'entity-filter']);
        $filterQuery->setQuery(BaseSolrEntity::FIELD_ENTITY_TYPE.':"'.$entityType.'"');
        $select->addFilterQueries([$filterQuery]);

        return $this->solrClient->select($select);
    }
}
