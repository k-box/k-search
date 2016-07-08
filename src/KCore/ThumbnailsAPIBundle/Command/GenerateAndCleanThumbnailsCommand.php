<?php

namespace KCore\ThumbnailsAPIBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAndCleanThumbnailsCommand extends ContainerAwareCommand
{

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('thumbnails:generate-and-clean')
            ->setDescription('Create next thumbnail in queue and clean expired temp files')
        ;
    }

    /**
     * @inheritdoc
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $thumbnailsService = $this->getContainer()->get('klink.thumbnails.service');
        $thumbnailsService->deleteExpiredFiles();
        $thumbnailsService->generateNextThumbnailInQueue();
    }
}
