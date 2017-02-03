<?php

namespace KCore\CoreBundle\Command;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Services\CoreService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

class DocumentAddCommand extends ContainerAwareCommand
{
    /** @var CoreService */
    protected $coreService;

    protected function configure()
    {
        $this->setName('kcore:document:add')
            ->setDescription('Add a document to one Cores')
            ->addArgument('descriptor-core', InputArgument::REQUIRED, 'Specify which core to use: [public|private]')
            ->addArgument('file', InputArgument::REQUIRED, 'Specify the file to add')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument('file');

        $documentDescriptor = $this->buildDescriptor($input);
        $documentService = $this->getContainer()->get('klink.document.service');

        $file = new File($fileName);

        $result = null;
        try {
            $result = $documentService->indexDocumentDescriptor($documentDescriptor, $file);
            $output->writeln('Document saved: return value='.$result->getResponse()->getStatusCode());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        if ($result->getStatus() != 0) {
            throw new \Exception($result->getResponse()->getStatusMessage());
        }

        return 0;
    }

    /**
     * Returns the Solarium Client given the core name.
     *
     * @param string $coreName
     *
     * @return string|null
     */
    private function getVisibilityByCore($coreName)
    {
        switch ($coreName) {
            case 'private':
                return DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE;
                break;
            case 'public':
                return DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC;
                break;
        }

        return null;
    }

    /**
     * @param InputInterface $input
     *
     * @return DocumentDescriptor
     */
    private function buildDescriptor(InputInterface $input)
    {
        $core = $input->getArgument('descriptor-core');
        $file = $input->getArgument('file');
        $fileName = basename($file);

        $institutionId = 'FAKE';
        $localDocumentId = substr(hash('md5', $fileName), 0, 5);

        $hashAlgorithm = $this->getContainer()->getParameter('kcore.document_descriptor_hash');
        $hash = hash_file($hashAlgorithm, $file);
        $mimeType = 'application/pdf';
        $documentType = 'document';
        $userOwner = $userUploader = 'text <test@example.com>';
        $documentUri = 'http://fake.ocm/doc';
        $thumbnailUri = $documentUri.'/thumbnail';

        $descriptor = new DocumentDescriptor($institutionId, $localDocumentId);
        $descriptor->setVisibility($this->getVisibilityByCore($core));
        $descriptor->setCreationDate(new \DateTime());
        $descriptor->setHash($hash);
        $descriptor->setMimeType($mimeType);
        $descriptor->setDocumentType($documentType);
        $descriptor->setUserOwner($userOwner);
        $descriptor->setUserUploader($userUploader);
        $descriptor->setDocumentURI($documentUri);
        $descriptor->setThumbnailURI($thumbnailUri);
        $descriptor->setTitle($fileName);

        return $descriptor;
    }
}
