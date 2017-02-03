<?php

namespace KCore\CoreBundle\Command;

use KCore\CoreBundle\Services\CoreService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

class DocumentExtractTextCommand extends ContainerAwareCommand
{
    /** @var CoreService */
    protected $coreService;

    protected function configure()
    {
        $this->setName('kcore:document:extract-text')
            ->setDescription('Extracts a text from a file')
            ->addArgument('file', InputArgument::REQUIRED, 'Specify the file to add')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument('file');
        $isPublic = true;

        $textExtractorService = $this->getContainer()->get('klink.textextractor.service');
        try {
            $file = new File($fileName);

            $contents = $textExtractorService->extractText($file, $isPublic);
            var_dump($contents);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return 0;
    }
}
