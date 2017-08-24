<?php

namespace App\Command;

use App\Service\DataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataForProcessingQueueWorkerCommand extends Command
{
    /**
     * @var DataService
     */
    private $dataService;

    /**
     * DataForProcessingQueueWorkerCommand constructor.
     *
     * @param mixed $name
     */
    public function __construct($name, DataService $dataService)
    {
        parent::__construct($name);

        $this->dataService = $dataService;
    }

    protected function configure()
    {
        $this->setName('ksearch:data-for-processing-queue:worker')
            ->setDescription('It goes through the Data for processing queue downloading the documents and trying to get the textual content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (true) {
            try {
                $output->writeln('<info>Polling the queue</info>');
                if ($this->dataService->processDataFromQueue()) {
                    $output->writeln('<info>Item processed</info>');
                }
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getCode().'</error>');
            }
        }
    }
}
