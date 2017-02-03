<?php

namespace KCore\ThumbnailsAPIBundle\Library;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;

class ThumbnailFromPDF
{
    protected $pdfBoxJarPath = null;
    protected $timeout = null;

    public function __construct($pdfBoxJar, $timeout)
    {
        $this->pdfBoxJarPath = $pdfBoxJar;
        $this->timeout = $timeout;
    }

    /**
     * @param File   $queueFile
     * @param string $partFile
     */
    public function generateThumbnail(File $queueFile, $partFile)
    {
        $jar = $this->pdfBoxJarPath;
        $cmd = 'java -jar '.$jar.' PDFToImage';
        $options = "-imageType png -outputPrefix $partFile -endPage 1";

        $command = escapeshellcmd($cmd.' '.$options.' '.$queueFile);
        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $fs = new Filesystem();
        $fs->rename($partFile.'1.png', $partFile);

        $mogrifyCmd = 'mogrify -format png -thumbnail 500x500 '; //-write " . $partFile;
        $command = escapeshellcmd($mogrifyCmd.' '.$partFile);
        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}
