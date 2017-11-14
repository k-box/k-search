<?php

namespace App\Command;

use App\Model\Data\Data;
use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataDeleteCommand extends ContainerAwareCommand
{
    /** @var DataService */
    private $dataService;

    public function __construct(DataService $dataService)
    {
        parent::__construct();
        $this->dataService = $dataService;
    }

    protected function configure()
    {
        $this->setName('ksearch:data:delete')
            ->setDescription('')
            ->addArgument('uuid', InputArgument::REQUIRED, 'Use UUID to remove from the indexed.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uuid = $input->getArgument('uuid');

        $output->write(sprintf('Deleting Data from index. UUID: <comment>%s</comment>', $uuid));
        if ($this->dataService->deleteData($uuid)) {
            $output->writeln(' ... Ok');

            return 0;
        }

        $output->writeln(' ... <error>Failure</error>');

        return 1;
    }
}
