<?php

namespace App\Command;

use App\Service\DataDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataComputeHash extends Command
{
    protected static $defaultName = 'ksearch:data:compute-hash';

    /**
     * @var DataDownloader
     */
    private $downloader;

    public function __construct(DataDownloader $downloader)
    {
        parent::__construct();
        $this->downloader = $downloader;
    }

    protected function configure()
    {
        $this
            ->setDescription('Compute the HASH of file')
            ->addArgument('filename', InputArgument::REQUIRED, 'The file to compute the HASH for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = (string) $input->getArgument('filename');
        $hash = $this->downloader->computeFileHash($filename);

        $output->writeln('Computed hash for file <info>'.$filename.'</info>', OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln($hash);
    }
}
