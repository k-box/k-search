<?php

namespace App\Service;

use App\Entity\BaseSolrEntity;
use App\Exception\InternalSearchException;
use Solarium\Client;

class SolrService
{
    /**
     * @var Client
     */
    private $solrClient;

    /**
     * SearchService constructor.
     *
     * @param Client $solrClient
     */
    public function __construct(Client $solrClient)
    {
        $this->solrClient = $solrClient;
    }

    public function delete(string $entityType, string $id): bool
    {
        $update = $this->solrClient->createUpdate();
        $query = ' +'.BaseSolrEntity::FIELD_ENTITY_TYPE.':'.$entityType.
                 ' +'.BaseSolrEntity::FIELD_ENTITY_ID.':'.$id;

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
