<?php

namespace KCore\ThumbnailsAPIBundle\Command;

use KCore\ThumbnailsAPIBundle\Services\ThumbnailsService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateThumbnailsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kcore:thumbnails:generate')
            ->setDescription('Create next thumbnail in queue')
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
        $thumbnailsService->generateNextThumbnailInQueue();
    }
}
