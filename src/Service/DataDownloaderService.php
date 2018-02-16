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
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class DataDownloaderService
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var string
     */
    private $downloadPath;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MimeTypeGuesser
     */
    private $mimeTypeGuesser;

    public function __construct(
        HTTPClient $httpClient,
        MessageFactory $messageFactory,
        MimeTypeGuesserInterface $mimeTypeGuesser,
        Filesystem $filesystem,
        string $downloadPath,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->messageFactory = $messageFactory;
        $this->mimeTypeGuesser = $mimeTypeGuesser;
        $this->filesystem = $filesystem;
        $this->downloadPath = $downloadPath;
        $this->logger = $logger;
    }

    /**
     * Removes the downloaded contents for the given data, if any.
     *
     * @param Data $data
     *
     * @throws IOException When removal fails
     */
    public function removeDownloadedDataFile(Data $data)
    {
        if ($this->isDataFileDownloaded($data)) {
            $filename = $this->buildDownloadDataFilename($data);
            $this->filesystem->remove($filename);
        }
    }

    /**
     * Downloads the file of the given data and returns the file containing them.
     *
     * @param Data $data The Data model
     *
     * @throws DataDownloadErrorException
     *
     * @return \SplFileInfo
     */
    public function getDataFile(Data $data): \SplFileInfo
    {
        $downloadDataFilename = $this->buildDownloadDataFilename($data);

        // If the file is not there yet, let's proceed and download it
        if ($this->isDataFileDownloaded($data)) {
            $this->logger->debug('File already downloaded, skipping URL request {uuid}', [
                'uuid' => $data->uuid,
                'downloaded' => $downloadDataFilename,
            ]);
            return new \SplFileInfo($downloadDataFilename);
        }

        $this->logger->debug('Downloading file for {uuid}, url={url}', [
            'uuid' => $data->uuid,
            'url' => $data->url,
        ]);

        $request = $this->messageFactory->createRequest('GET', $data->url);
        $response = $this->handleRequest($data, $request);

        try {
            $this->filesystem->dumpFile($downloadDataFilename, $response->getBody()->detach());
        } catch (IOException $exception) {
            // Something went wrong while storing the file, wrap the exception
            throw new DataDownloadErrorException(
                sprintf('Unable to store the file for Data %s in %s.', $data->uuid, $downloadDataFilename),
                0,
                $exception
            );
        }

        return new \SplFileInfo($downloadDataFilename);
    }

    /**
     * Return the guessed mime-type of the Data file.
     *
     * @param Data $data
     *
     * @throws DataDownloadErrorException
     *
     * @return string|null
     */
    public function getDataFileMimetype(Data $data): ?string
    {
        if (!$this->isDataFileDownloaded($data)) {
            $headers = $this->getDataUrlHeaders($data);
            if (!$headers || !isset($headers['Content-Type'])) {
                return null;
            }

            // Get the MimeType from the Content-Type header as defined here: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
            return current(explode(';', current($headers['Content-Type']), 2));
        }

        try {
            return $this->mimeTypeGuesser->guess($this->buildDownloadDataFilename($data));
        } catch (\Exception $exception) {
            $this->logger->error('Error guessing downloaded file mime-type for {uuid} at {url}: {message}', [
                'uuid' => $data->uuid,
                'url' => $data->url,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return null;
        }
    }

    /**
     * Returns the headers fetched from the Data url.
     *
     * @param Data $data
     *
     * @throws DataDownloadErrorException
     *
     * @return string[][]
     */
    private function getDataUrlHeaders(Data $data): array
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
        } catch (\Exception | Exception $exception) {
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
    private function buildDownloadDataFilename(Data $data): string
    {
        $subFolder = substr($data->uuid, 0, 2);

        return $this->downloadPath.
            DIRECTORY_SEPARATOR.$subFolder.
            DIRECTORY_SEPARATOR.$data->uuid;
    }

    /**
     * Check if the file for the given data has been downloaded.
     *
     * @param Data $data
     *
     * @return bool
     */
    private function isDataFileDownloaded(Data $data): bool
    {
        $filename = $this->buildDownloadDataFilename($data);

        return $this->filesystem->exists($filename);
    }
}
