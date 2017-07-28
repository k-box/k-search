<?php

namespace App\Service;

use App\Entity\DataSolrEntity;

class DataService
{
    /**
     * @var SolrService
     */
    private $solrService;

    /**
     * SearchService constructor.
     *
     * @param SolrService $solrService
     */
    public function __construct(SolrService $solrService)
    {
        $this->solrService = $solrService;
    }

    public function deleteData(string $uuid)
    {
        return $this->solrService->delete(DataSolrEntity::getEntityType(), $uuid);
    }
}
