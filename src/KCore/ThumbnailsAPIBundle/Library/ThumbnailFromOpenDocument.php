<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 11/03/2015
 * Time: 22:36
 */

namespace KCore\ThumbnailsAPIBundle\Library;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;

class ThumbnailFromOpenDocument {

    protected $timeout;

    /**
     * @param $timeout
     */
    function __construct($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param File $queueFile
     * @param string $partFile
     */
    public function generateThumbnail(File $queueFile, $partFile) {
        $zip = new \ZipArchive;
        if (true === $zip->open($queueFile)) {
            $content = $zip->getFromName("Thumbnails/thumbnail.png");
            $zip->close();
            if ($content) {
                $fs = new Filesystem();
                $fs->dumpFile($partFile, $content);
                $convertCmd = "mogrify -format png -thumbnail 500x500 " . $partFile;
                $command = escapeshellcmd($convertCmd);
                $process = new Process($command);
                $process->setTimeout($this->timeout);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($process->getErrorOutput());
                }
            }
        }
    }
}