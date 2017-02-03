<?php

namespace KCore\CoreBundle\Command;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Libraries\KCoreHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentDeleteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('kcore:document:delete')
            ->setDescription('Deletes a Document from a core')
            ->addArgument('core', InputArgument::REQUIRED, 'Specify which core to optimize: [public|private]')
            ->addArgument('institutionId', InputArgument::REQUIRED, '')
            ->addArgument('documentId', InputArgument::REQUIRED, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentService = $this->getContainer()->get('klink.document.service');

        $availableCores = ['private', 'public'];
        $core = $input->getArgument('core');
        $institutionId = $input->getArgument('institutionId');
        $documentId = $input->getArgument('documentId');
        $fullId = DocumentDescriptor::computeDocumentId($institutionId, $documentId);

        if (!in_array($core, $availableCores)) {
            $output->writeln('<error>Wrong Core name: '.$core.'</error>');

            return -1;
        }

        $visibility = KCoreHelper::getVisibilityByCore($core);
        $output->writeln('Deleting document from core: <comment>'.$core.'</comment>, id: <info>'.$fullId.'</info>');

        try {
            $documentDescriptor = $documentService->getDocumentDescriptor($institutionId, $documentId, $visibility);

            if (!$documentDescriptor) {
                $output->writeln('Document not Found');

                return -1;
            }
            $result = $documentService->deleteDocumentDescriptor($documentDescriptor);

            var_dump($result->getResponse()->getStatusCode());
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getCode().'</error>');
        }

        return 0;
    }
}
