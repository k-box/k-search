<?php

namespace App\Command;

use App\Model\Data\Data;
use App\Service\DataDownloaderService;
use App\Service\DataService;
use App\Service\QueueService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
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
        $this->setName('ksearch:test')
            ->setDescription(
                'It goes through the Data for processing queue downloading the documents and trying to get the textual content'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Getting File</info>');
        $data = new Data();
        $data->uuid = '1212121921920190210';
        $data->url = 'http://dl-cdn.alpinelinux.org/alpine/v3.6/releases/x86_64/alpine-standard-3.6.2-x86_64.iso';

        $this->dataDownloaderService->downloadDataContents($data);
    }
}
