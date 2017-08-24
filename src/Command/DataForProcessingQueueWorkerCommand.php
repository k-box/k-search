<?php

namespace App\Command;

use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataForProcessingQueueWorkerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('ksearch:data-for-processing-queue:worker')
            ->setDescription('It goes through the Data for processing queue downloading the documents and trying to get the textual content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dataService = $this->getContainer()->get(DataService::class);

        while (true) {
            try {
                $output->writeln('<info>Polling the queue</info>');
                if ($dataService->processDataFromQueue()) {
                    $output->writeln('<info>Item processed</info>');
                }
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getCode().'</error>');
            }
        }
    }
}
