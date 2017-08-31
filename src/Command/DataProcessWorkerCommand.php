<?php

namespace App\Command;

use App\Queue\Message\UUIDMessage;
use App\Service\DataService;
use App\Service\QueueService;
use Bernard\Queue;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DataProcessWorkerCommand extends ContainerAwareCommand
{
    /** @var string */
    private $tempFolder;

    /** @var QueueService */
    private $queueService;

    /** @var DataService */
    private $dataService;

    /** @var HttpClient */
    private $httpClient;

    /** @var MessageFactory */
    private $messageFactory;

    public function __construct(
        QueueService $queueService, DataService $dataService, HttpClient $httpClient, MessageFactory $messageFactory, $tempFolder = null
    ) {
        parent::__construct();
        $this->queueService = $queueService;
        $this->dataService = $dataService;
        $this->httpClient = $httpClient;
        $this->tempFolder = $tempFolder ?: sys_get_temp_dir();

        $this->messageFactory = $messageFactory;
    }

    protected function configure()
    {
        $this->setName('ksearch:data-process:worker')
            ->setDescription('It goes through the Data for processing queue downloading the documents and trying to get the textual content')
            ->addOption('loops', null, InputOption::VALUE_OPTIONAL, 'How many asks to the queue. Default is infinite.', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loops = $input->getOption('loops');

        $isInfinite = false;
        if ($loops == 0) {
            $isInfinite = true;
        }

        for($i = 0; $i < $loops || $isInfinite; $i++)
        {
            try {
                $output->writeln('<info>Getting next UUID to process from queue</info>');

                $message = $this->queueService->dequeMessage(QueueService::DATA_PROCESS_QUEUE);

                if ($message instanceof UUIDMessage) {
                    $data = $this->dataService->getData($message->getUUID());
                    $request = $this->messageFactory->createRequest('GET', $data->url);
                    $response = $this->httpClient->sendRequest($request);

                    $file = $this->saveResponseAsFile($response, $message->getUUID());

                    if (!$file) {
                        $output->writeln(sprintf('<error>Error retrieving the file for document %s</error>', $message->getUUID()));
                        $this->queueService->enqueueMessage(QueueService::DATA_PROCESS_QUEUE, $message);
                    } else {
                        // Index the data with text extraction from the file
                        $this->dataService->addDataWithFileExtraction($data, $file);
                        $output->writeln('<info>Item processed</info>');
                    }
                }
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');
                // @todo Expose the error in the API?
            }
        }
    }

    /**
     * @param $response
     * @param $message
     * @param mixed $uuid
     *
     * @return false|\SplFileInfo
     */
    protected function saveResponseAsFile(ResponseInterface $response, $uuid)
    {
        $bodyStream = $response->getBody();


        $originalStream = fopen(sprintf('data://text/plain;base64,%s', base64_encode($bodyStream->getContents())), 'r');

        $file = sprintf('%s/%s', $this->tempFolder, $uuid);
        $destStream = fopen($file, 'w');
        $copiedBytes = stream_copy_to_stream($originalStream, $destStream);

        $isAllCopied = $copiedBytes === $bodyStream->getSize();

        fclose($destStream);
        fclose($originalStream);

        if (!$isAllCopied) {
            return false;
        }

        return new \SplFileInfo($file);
    }
}
