<?php

namespace App\Command;

use App\Manager\DataManager;
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
        /* @var DataManager $coreService */
        $dataService = $this->getContainer()->get(DataManager::class);
/*
        try {
            $update = $client->createUpdate();
            $update->addOptimize(true, false, 1);
            $result = $client->update($update);

            $result = $result->getStatus();

            $output->writeln('<info>OK</info> (result code: '.$result.')');
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getCode().'</error>');
        }

        return 0;*/
    }

}
