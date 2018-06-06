<?php

namespace App\Command;

use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataStatusCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'ksearch:data:status';

    /** @var DataService */
    private $dataService;

    public function __construct(DataService $dataService)
    {
        parent::__construct();
        $this->dataService = $dataService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Returns the status of a given data from the index')
            ->addArgument('uuid', InputArgument::REQUIRED, 'Use UUID to fetch the status for.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uuid = $input->getArgument('uuid');

        $output->writeln(sprintf('Getting Data status from index. UUID: <comment>%s</comment>', $uuid));
        $data = $this->dataService->getData($uuid);
        $output->writeln('Status = <info>'.$data->status.'</info>');
    }
}
