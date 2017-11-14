<?php

namespace App\Service;

use App\Exception\DataDownloadErrorException;
use App\Model\Data\Data;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DataDownloaderService
{
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
        HTTPClient $httpClient,
        MessageFactory $messageFactory,
        string $tempFolder,
        LoggerInterface $logger
    ) {
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
        $response = $this->handleRequest($data, $request);

        $tempFile = $this->buildTempFilenameForData($data);
        $this->fileSystem->mkdir($this->tempFolder);

        file_put_contents($tempFile, $response->getBody()->detach());

        return new \SplFileInfo($tempFile);
    }

    public function getDataUrlHeaders(Data $data): array
    {
        $this->logger->debug('Downloading HEADERS for: {uuid}, url={url}', [
            'uuid' => $data->uuid,
            'url' => $data->url,
        ]);

        $request = $this->messageFactory->createRequest('HEAD', $data->url);
        $response = $this->handleRequest($data, $request);

        return $response->getHeaders();
    }

    /**
     * @param Data             $data
     * @param RequestInterface $request
     *
     * @throws DataDownloadErrorException
     *
     * @return ResponseInterface
     */
    private function handleRequest(Data $data, RequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (Exception $exception) {
            $this->logger->warning('Exception while executing {method} request for {uuid}.', [
                'method' => $request->getMethod(),
                'uuid' => $data->uuid,
                'url' => $data->url,
                'exception' => $exception,
            ]);

            // Something went wrong with the request, rethrowing the exception
            throw new DataDownloadErrorException(
                sprintf('Unable to download contents for Data %s from %s.', $data->uuid, $data->url), 0, $exception
            );
        }

        if (200 !== $response->getStatusCode()) {
            $this->logger->warning('Wrong http code returned  while executing {method} request for {uuid}: HTTP {code}.', [
                'method' => $request->getMethod(),
                'uuid' => $data->uuid,
                'code' => $response->getStatusCode(),
                'url' => $data->url,
            ]);

            throw new DataDownloadErrorException(
                sprintf('Wrong response while downloading contents for Data %s from %s. Got HTTP %s response code.', $data->uuid, $data->url, $response->getStatusCode())
            );
        }

        return $response;
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
