<?php

namespace App\Service;

use App\Model\Data\Data;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DataDownloaderService
{
    /** @var DataService */
    private $dataService;

    /** @var HttpClient */
    private $httpClient;

    /** @var Filesystem */
    private $fileSystem;

    /** @var MessageFactory */
    private $messageFactory;

    /** @var string */
    private $tempFolder;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        DataService $dataService,
        HTTPClient $httpClient,
        MessageFactory $messageFactory,
        string $tempFolder,
        LoggerInterface $logger
    ) {
        $this->dataService = $dataService;
        $this->httpClient = $httpClient;
        $this->messageFactory = $messageFactory;
        $this->fileSystem = new Filesystem();
        $this->tempFolder = $tempFolder;
        $this->logger = $logger;
    }

    /**
     * Removes the downloaded contents for the given data, if any.
     *
     * @param Data $data
     *
     * @throws IOException When removal fails
     */
    public function removeDataContents(Data $data)
    {
        $filename = $this->buildTempFilenameForData($data);
        if ($this->fileSystem->exists($filename)) {
            $this->fileSystem->remove($filename);
        }
    }

    /**
     * Downloads the contents of the given data and returns the file containing them.
     *
     * @param Data $data The Data model
     *
     * @return \SplFileInfo
     */
    public function downloadDataContents(Data $data): \SplFileInfo
    {
        $this->logger->debug('Downloading: {uuid}, url={url}', [
            'uuid' => $data->uuid,
            'url' => $data->url,
        ]);

        $request = $this->messageFactory->createRequest('GET', $data->url);
        $response = $this->httpClient->sendRequest($request);

        $tempFile = $this->buildTempFilenameForData($data);
        $this->fileSystem->mkdir($this->tempFolder);

        file_put_contents($tempFile, $response->getBody()->detach());

        return new \SplFileInfo($tempFile);
    }

    /**
     * Builds the temporary filename for the data contents.
     *
     * @param Data $data The Data model
     *
     * @return string
     */
    private function buildTempFilenameForData(Data $data): string
    {
        return $this->tempFolder.DIRECTORY_SEPARATOR.$data->uuid;
    }
}
