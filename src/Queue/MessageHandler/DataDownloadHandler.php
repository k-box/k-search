<?php

namespace App\Queue\MessageHandler;

use App\Exception\DataDownloadErrorException;
use App\Exception\SolrEntityNotFoundException;
use App\Queue\Message\DataDownloadMessage;
use App\Service\DataDownloader;
use App\Service\DataService;
use Psr\Log\LoggerInterface;

class DataDownloadHandler
{
    /**
     * @var DataService
     */
    private $dataService;

    /**
     * @var DataDownloader
     */
    private $dataDownloaderService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DataService $dataService,
        DataDownloader $dataDownloaderService,
        LoggerInterface $logger
    ) {
        $this->dataService = $dataService;
        $this->dataDownloaderService = $dataDownloaderService;
        $this->logger = $logger;
    }

    public function __invoke(DataDownloadMessage $dataMessage): void
    {
        try {
            $data = $this->dataService->getData($dataMessage->getUuid());
        } catch (SolrEntityNotFoundException $e) {
            $this->logger->info('Can not download file for data {uuid}: Data not found in the index!', [
                'uuid',
            ]);

            return;
        }

        if ($dataMessage->getRequestId() !== $data->requestId) {
            $this->logger->info('Can not download file for data {uuid}: RequestID is not matching the queue message', [
                'uuid',
            ]);

            return;
        }

        try {
            // Download the file and log the result
            $dataFilename = $this->dataDownloaderService->getDataFile($data);
            $this->logger->info('File for data {uuid} has been downloaded to {filename}', [
                'uuid' => $data->uuid,
                'filename' => $dataFilename->getPath().\DIRECTORY_SEPARATOR.$dataFilename->getFilename(),
            ]);
        } catch (DataDownloadErrorException $exception) {
            $this->logger->error(
                'Error getting data for {uuid}: {message}',
                [
                    'uuid' => $data->uuid,
                    'error' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );
        }
    }
}
