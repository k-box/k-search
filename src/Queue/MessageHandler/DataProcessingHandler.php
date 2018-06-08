<?php

namespace App\Queue\MessageHandler;

use App\Exception\DataDownloadErrorException;
use App\Exception\InternalSearchException;
use App\Exception\ProcessingStatusNotFoundException;
use App\Exception\SolrExtractionException;
use App\Helper\DateHelper;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Queue\Message\DataProcessingMessage;
use App\Service\DataDownloader;
use App\Service\DataProcessingService;
use App\Service\DataService;
use App\Service\DataStatusService;
use Psr\Log\LoggerInterface;

class DataProcessingHandler
{
    /**
     * @var DataService
     */
    private $dataService;

    /**
     * @var DataProcessingService
     */
    private $dataProcessingService;

    /**
     * @var DataDownloader
     */
    private $dataDownloaderService;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var DataStatusService
     */
    private $dataStatusService;

    public function __construct(
        DataService $dataService,
        DataStatusService $dataStatusService,
        DataProcessingService $dataProcessingService,
        DataDownloader $dataDownloaderService,
        LoggerInterface $logger
    ) {
        $this->dataService = $dataService;
        $this->dataDownloaderService = $dataDownloaderService;
        $this->logger = $logger;
        $this->dataProcessingService = $dataProcessingService;
        $this->dataStatusService = $dataStatusService;
    }

    public function __invoke(DataProcessingMessage $dataMessage): void
    {
        try {
            $processing = $this->dataProcessingService->getProcessingStatus($dataMessage->getUuid(), $dataMessage->getRequestId());
        } catch (ProcessingStatusNotFoundException $e) {
            $this->logger->warning(
                'Processing request for not existing status (outdated status?)',
                [
                    'uuid' => $dataMessage->getUuid(),
                    'requestId' => $dataMessage->getRequestId(),
                ]
            );

            return;
        }

        // Ensure the indexed data is not newer than the one were adding
        if ($this->ensureDataIsNotNewer($processing->getDataUuid(), $processing->getAddedAt())) {
            $this->handleIndexing($processing->getData());
        }

        // We're done, delete the status from the DB
        $this->dataProcessingService->deleteProcessingStatusByCriteria(
            $processing->getDataUuid(),
            $processing->getRequestId(),
            DataStatus::STATUS_QUEUED_OK
        );
    }

    public static function getSubscribedTopics(): array
    {
        return [
            DataProcessingService::DATA_QUEUED_TOPIC,
        ];
    }

    private function handleIndexing(Data $data): void
    {
        try {
            // Download
            $dataFilename = $this->dataDownloaderService->getDataFile($data);

            // Index
            $this->dataService->addDataWithFileExtraction($data, $dataFilename);
        } catch (DataDownloadErrorException $exception) {
            $this->logger->error(
                'Error getting data for {uuid}: {message}',
                [
                    'uuid' => $data->uuid,
                    'error' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );

            $this->updateDataWithStatus(
                $data,
                DataStatus::STATUS_DOWNLOAD_FAIL,
                'Error while downloading the Data contents'
            );
        } catch (InternalSearchException | SolrExtractionException $exception) {
            $this->logger->error(
                'Internal search exception while handling message for {uuid}: {message}',
                [
                    'uuid' => $data->uuid,
                    'error' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );

            $this->updateDataWithStatus(
                $data,
                DataStatus::STATUS_INDEX_FAIL,
                'An error occurred while extracting text from the Data'
            );
        }
    }

    private function updateDataWithStatus(Data $data, string $status, string $message = ''): void
    {
        // Ensure the indexed data is not newer than the one were adding
        if (!$this->ensureDataIsNotNewer($data->uuid, DateHelper::createUtcDate())) {
            return;
        }

        $data->status = $status;
        $data->errorStatus = $message;
        $this->logger->alert('Updating data uuid={uuid} with status={status}, reason: {message}', [
            'uuid' => $data->uuid,
            'status' => $status,
            'error' => $message,
        ]);

        $this->dataService->addData($data, '**KSEARCH-PROCESSING**');
    }

    private function ensureDataIsNotNewer(string $uuid, \DateTimeInterface $dateTime): bool
    {
        if ($this->dataStatusService->isDataNewer($uuid, $dateTime)) {
            $this->logger->warning('Processing message discarded: indexed data is newer for {uuid}', [
                'uuid' => $uuid,
            ]);

            return false;
        }

        return true;
    }
}
