<?php

namespace KCore\CoreBundle\Command;

use KCore\CoreBundle\Libraries\KCoreHelper;
use KCore\CoreBundle\Services\CoreService;
use Solarium\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OptimizeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('kcore:optimize')
            ->setDescription('Optimize Solr cores')
            ->addOption('core', null,  InputOption::VALUE_REQUIRED, 'Specify which core to optimize: [public|private]')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var CoreService $coreService */
        $coreService = $this->getContainer()->get('klink.core.service');

        $cores = ['private', 'public'];
        $coreName = $input->getOption('core');

        if (!is_null($coreName)) {
            if (!in_array($coreName, $cores)) {
                $output->writeln('<error>Wrong Core name: '.$coreName.'</error>');

                return -1;
            }

            // Just update the given core.
            $cores = [$coreName];
        }

        foreach ($cores as $core) {
            $client = KCoreHelper::getClientByCoreName($coreService, $core);
            $output->write('Optimizing core: <comment>'.$core.'</comment> ... ');
            try {
                $this->doOptimize($client);
                $output->writeln('<info>OK</info>');
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getCode().'</error>');
            }
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
