<?php

namespace App\Service;

use App\Entity\BaseSolrEntity;
use App\Entity\SolrEntity;
use App\Exception\InternalSearchException;
use App\Exception\ResourceNotFoundException;
use Solarium\Client;
use Solarium\QueryType\Select\Query\FilterQuery;

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

    public function add(SolrEntity $solrEntity)
    {
        throw new \RuntimeException(__CLASS__.'::'.__FUNCTION__.'() Not implemented');
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

        /** @var SolrDocumentDescriptor $obj */
        $obj = $resultSet->getIterator()[0];

        return $obj->getDocumentDescriptor();
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
}
