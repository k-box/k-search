<?php

namespace KCore\CoreBundle\Command;

use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use KCore\CoreBundle\Libraries\KCoreHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentSearchCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('kcore:document:search')
            ->setDescription('Search a Document a Solr core')
            ->addArgument('core', InputArgument::REQUIRED, 'Specify which core to optimize: [public|private]')
            ->addArgument('terms', InputArgument::REQUIRED, 'The terms to search')
            ->addOption('start', null,  InputOption::VALUE_REQUIRED, 'Start number', 0)
            ->addOption('num', null,  InputOption::VALUE_REQUIRED, 'Number of items to retrieve', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = $input->getOption('start');
        $num = $input->getOption('num');
        $facets = $filters = [];

        $documentService = $this->getContainer()->get('klink.document.service');

        $availableCores = ['private', 'public'];
        $core = $input->getArgument('core');
        $terms = $input->getArgument('terms');

        if (!in_array($core, $availableCores)) {
            $output->writeln('<error>Wrong Core name: '.$core.'</error>');

            return -1;
        }

        $visibility = KCoreHelper::getVisibilityByCore($core);
        $output->writeln('Searching in core: <comment>'.$core.'</comment>, keywords: <info>'.$terms.'</info>');

        try {
            $solrResult = $documentService->searchDocumentDescriptor($terms, $visibility, $filters, $facets, $start, $num);

            $output->writeln('<info>'.$solrResult->getNumFound().'</info> Found');

            $pos = 0;
            foreach ($solrResult->getDocuments() as $result) {
                /* @var SolrDocumentDescriptor $result */
                $score = $result->getField('score');
                // $results->addItem($resultItem);
                $output->writeln(
                    $pos.' '.sprintf('%3.3f', $score).' '.
                    $result->getDocumentDescriptor()->getId().' '.
                    $result->getDocumentDescriptor()->getTitle()
                );

                ++$pos;
            }
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getCode().'</error>');
        }

        return 0;
    }
}
