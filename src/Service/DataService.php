<?php

namespace App\Service;

use App\Entity\SolrEntityData;
use App\Model\Data\Data;

class DataService
{
    /**
     * @var SolrService
     */
    private $solrService;

    public function __construct(SolrService $solrService)
    {
        $this->solrService = $solrService;
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
     * @param string $uuid the data UUID
     *
     * @return Data
     */
    public function getData(string $uuid): Data
    {
        /** @var SolrEntityData $solrEntityData */
        $solrEntityData = $this->solrService->get(SolrEntityData::getEntityType(), $uuid, SolrEntityData::class);

        return $solrEntityData->buildModel();
    }

    public function addData(Data $data)
    {
        $dataEntity = SolrEntityData::buildFromModel($data);
        // @todo: Handle indexable data (queye/download from source, verify hash)
        // @todo: Handle non-indexable data (use indexed-text)
        $this->solrService->add($dataEntity);
    }
}
