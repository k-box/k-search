<?php

namespace App\Service;

use App\Entity\SolrEntity;
use App\Entity\SolrEntityData;
use App\Helper\DataHelper;
use App\Model\Data\Data;
use App\Model\Data\SearchParams;
use App\Model\Data\SearchResults;
use DateTimeZone;
use Solarium\QueryType\Select\Result\AbstractDocument;

class DataService
{
    /**
     * @var SolrService
     */
    private $solrService;

    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var DataDownloaderService
     */
    private $downloaderService;

    public function __construct(QueueService $queueService, SolrService $solrService, DataDownloaderService $downloaderService)
    {
        $this->solrService = $solrService;
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
     * @todo: filter retrievable data, to return only "STATUS = OK" Data from the index
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

    /**
     * Adds teh specific data to the indexing queue, ff the textualContents are provided, the indexing is performed without queuing.
     *
     * @param Data        $data            The Data object
     * @param null|string $textualContents The textual contents
     *
     * @return bool
     */
    public function addData(Data $data, ?string $textualContents): bool
    {
        if (!$data->properties->updated_at) {
            $data->properties->updated_at = new \DateTime();
            $data->properties->updated_at->setTimezone(new DateTimeZone('UTC'));
        }

        if (empty($textualContents) && DataHelper::isIndexable($data)) {
            $data->status = SolrEntityData::DATA_STATUS_QUEUED;
            $this->queueService->enqueueUUID($data);
        } else {
            $data->status = SolrEntityData::DATA_STATUS_OK;
        }

        $dataEntity = SolrEntityData::buildFromModel($data);
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

        $data->status = SolrEntityData::DATA_STATUS_OK;

        $this->addData($data, $contents);

        return true;
    }

    public function queryData( SearchParams $searchParams)
    {
        $solrResult = $this->solrService->select($searchParams, SolrEntityData::class);


        $searchResult = new SearchResults();
        $searchResult->query = $searchParams;
        $searchResult->query_time = $solrResult->getQueryTime();
        $searchResult->total_matches = $solrResult->getNumFound();
        $searchResult->items = array_map(function (AbstractDocument $document) {
            $idField = SolrEntity::FIELD_ENTITY_ID;
            $documentId = $document->$idField;
            $entityData = new SolrEntityData($documentId, $document);
            return $entityData->buildModel();
        }, $solrResult->getDocuments());

        return $searchResult;
    }
}
