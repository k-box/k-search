<?php

namespace App\Service;

use App\Entity\SolrEntityData;
use App\Helper\DataHelper;
use App\Model\Data\Data;
use DateTimeZone;

class DataService
{
    /**
     * @var SolrService
     */
    private $solrService;
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var DataDownloaderService
     */
    private $downloaderService;

    public function __construct(QueueService $queueService, SolrService $solrService, DataHelper $dataHelper, DataDownloaderService $downloaderService)
    {
        $this->solrService = $solrService;
        $this->dataHelper = $dataHelper;
        $this->queueService = $queueService;
        $this->downloaderService = $downloaderService;
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

    public function addData(Data $data, ?string $textualContents): bool
    {
        if (!$data->properties->updated_at) {
            $data->properties->updated_at = new \DateTime();
            $data->properties->updated_at->setTimezone(new DateTimeZone('UTC'));
        }

        $dataEntity = SolrEntityData::buildFromModel($data);

        if (empty($textualContents) && $this->dataHelper->isIndexable($data)) {
            $this->queueService->enqueueUUID($data);
        }

        $this->solrService->add($dataEntity, $textualContents);

        return true;
    }

    public function processDataFromQueue()
    {
        $dataUUID = $this->queueService->dequeueUUID();

        if (!$dataUUID) {
            return false;
        }

        $data = $this->getData($dataUUID);
        $contents = $this->downloaderService->getFileContents($data);
        $this->addData($data, $contents);

        return true;
    }
}
