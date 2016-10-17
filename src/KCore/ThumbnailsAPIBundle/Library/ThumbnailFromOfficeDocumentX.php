<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 11/03/2015
 * Time: 22:36.
 */
namespace KCore\ThumbnailsAPIBundle\Library;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;

class ThumbnailFromOfficeDocumentX
{
    protected $timeout;

    /**
     * @param $timeout
     */
    public function __construct($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param File   $queueFile
     * @param string $partFile
     */
    public function generateThumbnail(File $queueFile, $partFile)
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($queueFile)) {
            $content = $zip->getFromName('docProps/thumbnail.emf');
            $tempFile = $partFile.'.emf';
            if (!$content) {
                $content = $zip->getFromName('docProps/thumbnail.wmf');
                $tempFile = $partFile.'.wmf';
            }
            if (!$content) {
                $content = $zip->getFromName('docProps/thumbnail.jpeg');
                $tempFile = $partFile.'.jpeg';
            }
            $zip->close();
            if ($content) {
                $fs = new Filesystem();
                $fs->dumpFile($tempFile, $content);
                $convertCmd = 'convert -thumbnail 500x500 '.$tempFile.' '.$partFile;
                $command = escapeshellcmd($convertCmd);
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
