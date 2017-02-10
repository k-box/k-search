<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;

class ThumbnailGeneratorMSOfficeDocumentX extends AbstractThumbnailGenerator
{
    /**
     * @var int
     */
    private $timeout = null;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ThumbnailGeneratorMSOfficeDocumentX constructor.
     *
     * @param int             $timeout Timeout for thumbnail generation process
     * @param LoggerInterface $logger  The logger interface
     */
    public function __construct($timeout, $logger)
    {
        $this->timeout = $timeout;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function generateThumbnail(File $sourceFile, $thumbnailFilename, $width = 500, $height = 500, $format = 'png')
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($sourceFile)) {
            $content = $zip->getFromName('docProps/thumbnail.emf');
            $tempFile = $thumbnailFilename.'.emf';
            if (!$content) {
                $content = $zip->getFromName('docProps/thumbnail.wmf');
                $tempFile = $thumbnailFilename.'.wmf';
            }
            if (!$content) {
                $content = $zip->getFromName('docProps/thumbnail.jpeg');
                $tempFile = $thumbnailFilename.'.jpeg';
            }
            $zip->close();
            if ($content) {
                $fs = new Filesystem();
                $fs->dumpFile($tempFile, $content);

                $options = [
                    '-format' => $format,
                    '-thumbnail' => $height.'x'.$width,
                    '' => $tempFile.' '.$thumbnailFilename,
                ];

                $command = escapeshellcmd('convert '.self::buildShellCommandOptions($options, ' '));
                $process = new Process($command);
                $process->setTimeout($this->timeout);
                $process->run();

                $fs->remove($tempFile);

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($process->getErrorOutput());
                }
            }
        }
    }
}
