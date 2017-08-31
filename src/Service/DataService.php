<?php

namespace App\Service;

use App\Entity\SolrEntityData;
use App\Exception\BadRequestException;
use App\Helper\DataHelper;
use App\Model\Data\Data;
use App\Queue\Message\UUIDMessage;
use DateTimeZone;
use Symfony\Component\Finder\SplFileInfo;

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
     * Adds the specific data to the index.
     * If the textualContents are provided, the indexing is performed without queuing.
     *
     * @param Data        $data            The Data object
     * @param null|string $textualContents The textual contents to be indexed
     *
     * @throws BadRequestException if the data provided can not be indexed
     *
     * @return bool
     */
    public function addData(Data $data, ?string $textualContents = null): bool
    {
        $this->dataCleanup($data);

        $dataEntity = null;
        $enqueue = true;

        if (!empty($textualContents)) {
            // If the textual contents are provided, we straight index them
            $data->status = Data::DATA_STATUS_OK;
            $dataEntity = SolrEntityData::buildFromModel($data);
            $dataEntity->addTextualContents($textualContents);
            $enqueue = false;
        }

        if ($enqueue && DataHelper::isIndexable($data)) {
            // Otherwise, we queue the data to be indexed later.
            $data->status = Data::DATA_STATUS_QUEUED;
            $dataEntity = SolrEntityData::buildFromModel($data);
        }

        if (!$dataEntity) {
            // We are not able to index this data!
            throw new BadRequestException(['The given Data could not be indexed.']);
        }

        $this->solrService->add($dataEntity);

        if ($enqueue) {
            // We enqueue the data to be processed later, only if we were able to add it to the Index!
            $this->queueService->enqueueMessage(
                QueueService::DATA_PROCESS_QUEUE,
                new UUIDMessage($data->uuid)
            );
        }

        return true;
    }

    /**
     * Adds the specific data to the index, by extracting the text from the given file.
     *
     * @param Data        $data     The Data object
     * @param SplFileInfo $fileInfo The file to extract the textual contents from
     *
     * @return bool
     */
    public function addDataWithFileExtraction(Data $data, \SplFileInfo $fileInfo): bool
    {
        $this->dataCleanup($data);
        $data->status = Data::DATA_STATUS_OK;
        $dataEntity = SolrEntityData::buildFromModel($data);

        return $this->solrService->addWithTextExtraction($dataEntity, $fileInfo);
    }

    /**
     * Cleanup the given data, updates the missing fields.
     *
     * @param Data $data
     */
    protected function dataCleanup(Data $data)
    {
        if (!$data->properties->updated_at) {
            $data->properties->updated_at = new \DateTime('now', new DateTimeZone('UTC'));
        }
    }
}
