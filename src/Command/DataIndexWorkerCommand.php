<?php

namespace App\Command;

use App\Exception\DataDownloadErrorException;
use App\Exception\InternalSearchException;
use App\Exception\SolrEntityNotFoundException;
use App\Exception\SolrExtractionException;
use App\Model\Data\Data;
use App\Queue\Message\UUIDMessage;
use App\Service\DataDownloaderService;
use App\Service\DataService;
use App\Service\QueueService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class DataIndexWorkerCommand extends Command
{
    /** @var QueueService */
    private $queueService;

    /** @var DataDownloaderService */
    private $dataDownloaderService;

    /** @var DataService */
    private $dataService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        QueueService $queueService,
        DataService $dataService,
        DataDownloaderService $dataDownloaderService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->queueService = $queueService;
        $this->dataService = $dataService;
        $this->dataDownloaderService = $dataDownloaderService;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('ksearch:data:index-worker')
            ->setDescription(
                'It goes through the Data for processing queue downloading the documents and trying to get the textual content'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit the number of consumed messages. Default is to process an unlimited number of messages.',
                0
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');
        $consumedMessages = 0;

        $output->writeln('<info>Waiting for data to process...</info>');
        do {
            $message = $this->queueService->dequeMessage(QueueService::DATA_PROCESS_QUEUE);

            if (!$message instanceof UUIDMessage) {
                $this->logger->error('Error handling queue message, wrong message type {type} received', [
                    'type' => $message ? get_class($message) : 'null',
                ]);

                continue;
            }

            ++$consumedMessages;
            $this->logger->debug('Started processing message from queue {queue} for data-uuid={uuid}', [
                'queue' => $message->getQueue(),
                'uuid' => $message->getUUID(),
            ]);

            $output->writeln(sprintf('Processing uuid=<info>%s</info>', $message->getUUID()), Output::VERBOSITY_VERBOSE);

            try {
                $data = $this->dataService->getData($message->getUUID());
                $this->handleDataIndexing($data, $output);
            } catch (SolrEntityNotFoundException $e) {
                $this->logger->warning('Error handling message, Data document uuid={uuid} is not existing', [
                    'uuid' => $message ? $message->getUUID() : 'N/A',
                    'error' => $e->getMessage(),
                ]);
                $output->writeln([
                    sprintf('<error>Error loading data uuid=%s!</error>', $message->getUUID()),
                    $e->getMessage(),
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Error handling message, Data document uuid={uuid}: {error}', [
                    'uuid' => $message ? $message->getUUID() : 'N/A',
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]);
                $output->writeln([
                    sprintf('<error>Error while handling data message %s</error>', $message ? get_class($message) : 'null'),
                    $e->getMessage(),
                ]);
            }
        } while (0 === $limit || $consumedMessages < $limit);

        $output->writeln(sprintf('Exiting. Consumed messages: %d', $consumedMessages));
    }

    private function handleDataIndexing(Data $data, OutputInterface $output)
    {
        try {
            $output->writeln(' - Downloading contents', Output::VERBOSITY_VERY_VERBOSE);
            $dataFile = $this->dataDownloaderService->downloadDataContents($data);

            // Index the data with text extraction from the file
            $output->writeln(' - Indexing item', Output::VERBOSITY_VERY_VERBOSE);
            $this->dataService->addDataWithFileExtraction($data, $dataFile);
        } catch (DataDownloadErrorException $exception) {
            $this->logger->error('Error downloading data for {uuid}: {message}', [
                'uuid' => $data->uuid,
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            $output->writeln([
                sprintf('<error>An error occurred while Downloading the Data %s</error>', $data->uuid),
                $exception->getMessage(),
            ]);
            $this->updateDataWithError($data, $exception->getMessage());
        } catch (InternalSearchException | SolrExtractionException $exception) {
            $this->logger->error('Internal search exception while handling message for {uuid}: {message}', [
                'uuid' => $data->uuid,
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            $output->writeln([
                sprintf('An error occurred while extracting text from the Data %s', $data->uuid),
                $exception->getMessage(),
            ]);

            $this->updateDataWithError($data, 'An error occurred while extracting text from the Data');
        }

        // Remove the downloaded data
        $output->writeln(' - Removing downloaded contents', Output::VERBOSITY_VERBOSE);
        $this->dataDownloaderService->removeDataContents($data);
    }

    private function updateDataWithError(Data $data, string $errorStatus)
    {
        $data->status = Data::STATUS_ERROR;
        $data->errorStatus = $errorStatus;
        $this->logger->alert('Updating data uuid={uuid} with "error" status, reason: {error}', [
            'uuid' => $data->uuid,
            'error' => $errorStatus,
        ]);
        $this->dataService->addData($data, '**KSEARCH-ERROR**');
    }
}
