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
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class DataDownloader
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var MimeTypeGuesserInterface
     */
    private $mimeTypeGuesser;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var DataFileNameGenerator
     */
    private $nameGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        HTTPClient $httpClient,
        MessageFactory $messageFactory,
        MimeTypeGuesserInterface $mimeTypeGuesser,
        Filesystem $filesystem,
        DataFileNameGenerator $nameGenerator,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->messageFactory = $messageFactory;
        $this->mimeTypeGuesser = $mimeTypeGuesser;
        $this->filesystem = $filesystem;
        $this->nameGenerator = $nameGenerator;
        $this->logger = $logger;
    }

    /**
     * Removes the downloaded contents for the given data UUID, if any.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function removeDownloadedDataFile(string $uuid): bool
    {
        $filename = $this->getDataTempFilename($uuid);
        if (!$filename) {
            return false;
        }

        try {
            $this->filesystem->remove($filename);
            $this->logger->debug('Removed downloaded file for Data {uuid}', [
                'uuid' => $uuid,
                'filename' => $filename,
            ]);

            return true;
        } catch (IOException $exception) {
            $this->logger->error('Exception while deleting downloaded file for Data {uuid}: {message}', [
                'uuid' => $uuid,
                'filename' => $filename,
                'exception' => $exception,
                'error' => $exception->getMessage(),
            ]);

            return false;
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
        // If the file has been downloaded already, skip the download
        if ($filename = $this->dataFileExistsAndIsCurrent($data)) {
            $this->logger->debug('File already downloaded for {uuid}, skipping URL request', [
                'uuid' => $data->uuid,
                'downloaded' => $filename,
            ]);

            return new \SplFileInfo($filename);
        }

        $this->logger->debug('Downloading file for {uuid}, url={url}', [
            'uuid' => $data->uuid,
            'url' => $data->url,
        ]);

        $request = $this->messageFactory->createRequest('GET', $data->url);
        $response = $this->handleRequest($data, $request);

        try {
            $filename = $this->nameGenerator->buildDownloadDataFilename($data->uuid);

            $this->filesystem->dumpFile($filename, $response->getBody()->__toString());
        } catch (IOException $exception) {
            // Something went wrong while storing the file, wrap the exception
            throw new DataDownloadErrorException(
                sprintf('Unable to store the file for Data %s in %s.', $data->uuid, $filename),
                0,
                $exception
            );
        }

        return new \SplFileInfo($filename);
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
        $filename = $this->dataFileExistsAndIsCurrent($data);

        // If the file does no exists, get the mimetype from the URL
        if (!$filename) {
            $headers = $this->getDataUrlHeaders($data);
            if (!$headers || !$headers['Content-Type'] ?? null) {
                return null;
            }

            // Get the MimeType from the Content-Type header as defined here: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
            return current(explode(';', current($headers['Content-Type']), 2));
        }

        try {
            return $this->mimeTypeGuesser->guess($filename);
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
     * Removes the textual contents of the data, if previously stored.
     */
    public function removeStoredTextualContents(string $uuid): bool
    {
        $filename = $this->getDataTempFilename($uuid, DataFileNameGenerator::TEXT_CONTENTS_EXTENSION);
        if (!$filename) {
            return false;
        }
        try {
            $this->filesystem->remove($filename);
            $this->logger->debug('Removed textual-contents file for Data {uuid}', [
                'uuid' => $uuid,
                'filename' => $filename,
            ]);

            return true;
        } catch (IOException $exception) {
            $this->logger->error('Exception while deleting textual-contents file for Data {uuid}: {message}', [
                'uuid' => $uuid,
                'filename' => $filename,
                'exception' => $exception,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Stores the textual contents of the data.
     */
    public function storeDataTextualContents(string $uuid, string $textualContents)
    {
        $filename = $this->nameGenerator->buildDownloadDataFilename($uuid,
            DataFileNameGenerator::TEXT_CONTENTS_EXTENSION
        );
        $this->filesystem->dumpFile($filename, $textualContents);
    }

    /**
     * Returns the filename of the downloaded data, null if the file does not exists or the hash does not match.
     */
    public function dataFileExistsAndIsCurrent(Data $data): ?string
    {
        $filename = $this->getDataTempFilename($data->uuid);
        if (!$filename) {
            return null;
        }

        if ($data->hash !== $this->computeFileHash($filename)) {
            return null;
        }

        return $filename;
    }

    /**
     * Computes the HASH for the given filename.
     */
    public function computeFileHash(string $filename): string
    {
        return hash_file('sha512', $filename);
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
     * @throws DataDownloadErrorException
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
            $this->logger->warning(
                'Wrong http code returned  while executing {method} request for {uuid}: HTTP {code}.',
                [
                    'method' => $request->getMethod(),
                    'uuid' => $data->uuid,
                    'code' => $response->getStatusCode(),
                    'url' => $data->url,
                ]
            );

            throw new DataDownloadErrorException(
                sprintf(
                    'Wrong response while downloading contents for Data %s from %s. Got HTTP %s response code.',
                    $data->uuid,
                    $data->url,
                    $response->getStatusCode()
                )
            );
        }

        return $response;
    }

    /**
     * Get the current filename of the downloaded data, null if the file does not exists.
     */
    private function getDataTempFilename(string $uuid, ?string $ext = null): ?string
    {
        $filename = $this->nameGenerator->buildDownloadDataFilename($uuid, $ext);

        return $this->filesystem->exists($filename) ? $filename : null;
    }
}
