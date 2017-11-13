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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DataIndexWorkerCommand extends Command
{
    /** @var QueueService */
    private $queueService;

    /** @var DataDownloaderService */
    private $dataDownloaderService;

    /** @var DataService */
    private $dataService;

    public function __construct(
        QueueService $queueService,
        DataService $dataService,
        DataDownloaderService $dataDownloaderService
    ) {
        parent::__construct();
        $this->queueService = $queueService;
        $this->dataService = $dataService;
        $this->dataDownloaderService = $dataDownloaderService;
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

            try {
                ++$consumedMessages;

                if ($message instanceof UUIDMessage) {
                    $output->writeln(sprintf('Processing uuid=<info>%s</info>', $message->getUUID()));
                    $data = $this->dataService->getData($message->getUUID());

                    $this->handleDataIndexing($data, $output);
                } else {
                    $output->writeln(
                        sprintf('Error handling queue message, wrong message type %s received', get_class($message))
                    );
                }
            } catch (SolrEntityNotFoundException $e) {
                $output->writeln('Looks like the data does not exist!');
                $output->writeln('<error>'.$e->getMessage().'</error>');
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');
            }
            $output->write('.');
        } while (0 === $limit || $consumedMessages < $limit);

        $output->writeln(sprintf('Exiting. Consumed messages: %d', $consumedMessages));
    }

    private function handleDataIndexing(Data $data, OutputInterface $output)
    {
        try {
            $output->writeln(' - Downloading contents');
            $dataFile = $this->dataDownloaderService->downloadDataContents($data);

            // Index the data with text extraction from the file
            $output->writeln(' - Indexing item');
            $this->dataService->addDataWithFileExtraction($data, $dataFile);
        } catch (InternalSearchException|SolrExtractionException $exception) {
            $output->writeln('An error occurred while extracting text from the Data');
            $output->writeln($exception->getMessage());
            $this->updateDataWithError($data, 'An error occurred while extracting text from the Data');
        } catch (DataDownloadErrorException $exception) {
            $output->writeln('An error occurred while Downloading the data');
            $output->writeln($exception->getMessage());
            $this->updateDataWithError($data, $exception->getMessage());
        }

        // Remove the downloaded data
        $output->writeln(' - Removing downloaded contents');
        $this->dataDownloaderService->removeDataContents($data);
    }

    private function updateDataWithError(Data $data, string $errorStatus)
    {
        $data->status = Data::STATUS_ERROR;
        $data->errorStatus = $errorStatus;
        $this->dataService->addData($data, '**KSEARCH-ERROR**');
    }
}
