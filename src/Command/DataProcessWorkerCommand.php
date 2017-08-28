<?php

namespace App\Command;

use App\Queue\Message\UUIDMessage;
use App\Service\DataService;
use App\Service\QueueService;
use Http\Client\Common\PluginClient;
use Http\Message\MessageFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataProcessWorkerCommand extends ContainerAwareCommand
{
    /** @var QueueService */
    private $queueService;

    /** @var DataService */
    private $dataService;

    /** @var PluginClient */
    private $httpClient;

    /** @var MessageFactory */
    private $messageFactory;

    public function __construct(QueueService $queueService, DataService $dataService, PluginClient $httpClient, MessageFactory $messageFactory)
    {
        parent::__construct();
        $this->queueService = $queueService;
        $this->dataService = $dataService;
        $this->httpClient = $httpClient;
    }

    protected function configure()
    {
        $this->setName('ksearch:data-process:worker')
            ->setDescription('It goes through the Data for processing queue downloading the documents and trying to get the textual content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (true) {
            try {
                $output->writeln('<info>Getting next UUID to process from queue</info>');

                $message = $this->queueService->dequeMessage(QueueService::DATA_PROCESS_QUEUE);
                if ($message instanceof UUIDMessage) {
                    $data = $this->dataService->getData($message->getUUID());
                    $request = $this->messageFactory->createRequest('GET', $data->url);
                    $response = $this->httpClient->sendRequest($request);
                }

                // @todo Write contents to a temporary file from the $response object we got (use file streaming!)
                $file = null;

                // Index the data with text extraction from the file
                $this->dataService->addDataWithFileExtraction($data, $file);
                $output->writeln('<info>Item processed</info>');
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getCode().'</error>');
                // @todo Expose the error in the API?
            }
        }
    }
}
