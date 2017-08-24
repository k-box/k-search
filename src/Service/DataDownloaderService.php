<?php

namespace App\Service;

use App\HTTP\HTTPClient;
use App\Model\Data\Data;

class DataDownloaderService
{
    /**
     * @var HTTPClient
     */
    private $httpClient;

    public function __construct(HTTPClient $client)
    {
        $this->httpClient = $client;
    }

    public function getFileContents(Data $data): string
    {
        return $this->httpClient->requestFile($data->url);
    }
}
