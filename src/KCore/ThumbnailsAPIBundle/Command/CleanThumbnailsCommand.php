<?php

namespace KCore\ThumbnailsAPIBundle\Command;

use KCore\ThumbnailsAPIBundle\Services\ThumbnailsService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanThumbnailsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kcore:thumbnails:clean')
            ->setDescription('Clean expired thumbnail temp files')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ThumbnailsService $thumbnailsService */
        $thumbnailsService = $this->getContainer()->get('klink.thumbnails.service');
        $thumbnailsService->deleteExpiredFiles();
    }
}
