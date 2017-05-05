<?php

namespace App\Command;

use App\Services\CoreService;
use Solarium\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OptimizeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('ksearch:optimize')
            ->setDescription('Optimize Solr core')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var CoreService $coreService */
        $coreService = $this->getContainer()->get('ksearch.core.service');

        $client = $coreService->getCoreClient();

        $output->write('Optimizing core: <comment>'.$client->getEndpoint()->getBaseUri().'</comment> ... ');
        try {
            $update = $client->createUpdate();
            $update->addOptimize(true, false, 1);
            $result = $client->update($update);

            $result = $result->getStatus();

            $output->writeln('<info>OK</info> (result code: '.$result.')');
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getCode().'</error>');
        }

        return 0;
    }

    /**
     * Optimize a Collection, given a client.
     *
     * @param Client $client
     *
     * @return int
     */
    protected function doOptimize(Client $client)
    {
        $update = $client->createUpdate();
        $update->addOptimize(true, false, 1);
        $result = $client->update($update);

        return $result->getStatus();
    }
}
