<?php

namespace App\Service;

use App\Exception\InternalSearchException;
use Solarium\Client;

class SearchService
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

    public function deleteData(string $uuid)
    {
        $update = $this->solrClient->createUpdate();
        $update->addDeleteById($uuid);
        $update->addCommit();

        try {
            $result = $this->solrClient->update($update);

            return 0 === $result->getStatus();
        } catch (\Throwable $e) {
            throw new InternalSearchException(
                sprintf('Error while deleting Data %s', $uuid),
                $e->getCode(),
                $e
            );
        }
    }
}
