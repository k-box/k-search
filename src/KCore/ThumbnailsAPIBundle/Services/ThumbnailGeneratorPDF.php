<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;

class ThumbnailGeneratorPDF extends AbstractThumbnailGenerator
{
    /**
     * @var string
     */
    protected $pdfBoxJarPath = null;

    /**
     * @var int
     */
    private $timeout = null;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ThumbnailGeneratorPDF constructor.
     *
     * @param string          $pdfBoxJar The PDFBox JAR archive
     * @param int             $timeout   Timeout for thumbnail generation process
     * @param LoggerInterface $logger    The logger interface
     */
    public function __construct($pdfBoxJar, $timeout, $logger)
    {
        $this->pdfBoxJarPath = $pdfBoxJar;
        $this->timeout = $timeout;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function generateThumbnail(File $sourceFile, $thumbnailFilename, $width = 500, $height = 500, $format = 'png')
    {
        $cmd = 'java -jar '.$this->pdfBoxJarPath.' PDFToImage';
        $options = [
            '-format' => $format,
            '-prefix' => $thumbnailFilename,
            '-page' => '1',
            '' => $sourceFile->getRealPath(),
        ];

        $command = escapeshellcmd($cmd.' '.self::buildShellCommandOptions($options, ' '));

        $this->logger->warning($command);

        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->error('ThumbnailGeneratorPDF: Failed extracting PDF pages from "{file}" to "{tempFile}"', [
                'file' => $sourceFile->getFilename(),
                'tempFile' => $thumbnailFilename,
            ]);
            throw new \RuntimeException($process->getErrorOutput());
        }

        $this->logger->info('ThumbnailGeneratorPDF: Extracted PDF page 1 from "{file}" to "{tempFile}"', [
            'file' => $sourceFile->getFilename(),
            'tempFile' => $thumbnailFilename,
        ]);

        $fs = new Filesystem();
        $fs->rename($thumbnailFilename.'1.png', $thumbnailFilename);

        $options = [
            '-format' => $format,
            '-thumbnail' => $height.'x'.$width,
            '' => $thumbnailFilename,
        ];

        $command = escapeshellcmd('mogrify '.self::buildShellCommandOptions($options, ' '));
        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}
