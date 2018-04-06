<?php

namespace App\Service;

class DataFileNameGenerator
{
    /**
     * @var string
     */
    private $downloadPath;

    public function __construct(string $downloadPath)
    {
        $this->downloadPath = $downloadPath;
    }

    /**
     * Builds the cached filename for the data contents, from the.
     *
     * @param string $uuid
     *
     * @return string
     */
    public function buildDownloadDataFilename(string $uuid)
    {
        $subFolder = substr($uuid, 0, 2);

        return $this->downloadPath.
            DIRECTORY_SEPARATOR.$subFolder.
            DIRECTORY_SEPARATOR.$uuid;
    }
}
