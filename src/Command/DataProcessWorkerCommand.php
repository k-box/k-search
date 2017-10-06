<?php

namespace App\Command;

use App\Queue\Message\UUIDMessage;
use App\Service\DataDownloaderService;
use App\Service\DataService;
use App\Service\QueueService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DataProcessWorkerCommand extends ContainerAwareCommand
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
        $this->setName('ksearch:data-process:worker')
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
            try {
                $message = $this->queueService->dequeMessage(QueueService::DATA_PROCESS_QUEUE);
                ++$consumedMessages;

                if ($message instanceof UUIDMessage) {
                    $output->writeln(sprintf('Processing uuid=<info>%s</info>', $message->getUUID()));
                    $data = $this->dataService->getData($message->getUUID());

                    $output->write(' - Downloading contents ... ');
                    $dataFile = $this->dataDownloaderService->downloadDataContents($data);
                    $output->writeln('done');

                    // Index the data with text extraction from the file
                    $output->writeln(' - Indexing item ...');
                    $this->dataService->addDataWithFileExtraction($data, $dataFile);
                    $output->writeln('done');

                    // Remove the downloaded data
                    $output->writeln(' - Removing downloaded contents ...');
                    $this->dataDownloaderService->removeDataContents($data);
                    $output->writeln('done');
                }
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');
                // @todo Expose the error in the API?
            }
        } while (0 === $limit || $consumedMessages < $limit);

        $output->writeln(sprintf('Exiting. Consumed messages: %d', $consumedMessages));
    }
}
