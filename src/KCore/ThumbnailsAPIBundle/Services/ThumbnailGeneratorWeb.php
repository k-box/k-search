<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use JonnyW\PhantomJs\Client;
use JonnyW\PhantomJs\Client as PhantomJsClient;
use JonnyW\PhantomJs\Http\ResponseInterface;
use KCore\ThumbnailsAPIBundle\Exception\BadGatewayHttpException;
use KCore\ThumbnailsAPIBundle\Exception\GatewayMaxRedirectsHttpException;
use KCore\ThumbnailsAPIBundle\Exception\GatewayTimeoutHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class ThumbnailGeneratorWeb implements ThumbnailGeneratorInterface
{
    /**
     * @var Client
     */
    protected $phantomJsClient = null;

    /**
     * @var int
     */
    private $timeout = null;

    /**
     * @var int
     */
    private $maxRedirects = 1;

    /**
     * @var RecursiveValidator
     */
    private $validator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ThumbnailGeneratorPDF constructor.
     *
     * @param string             $binPath      The The PhantomJS executable file
     * @param int                $timeout      Timeout for thumbnail generation process, in milliseconds
     * @param int                $maxRedirects Maximum number of redirects to follow
     * @param RecursiveValidator $validator    The Validator
     * @param LoggerInterface    $logger       The logger interface
     */
    public function __construct($binPath, $timeout, $maxRedirects, $validator, $logger)
    {
        $this->timeout = $timeout;
        $this->maxRedirects = $maxRedirects;
        $this->validator = $validator;
        $this->logger = $logger;

        $client = PhantomJsClient::getInstance();
        $client->getEngine()->setPath($binPath);
        $this->phantomJsClient = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function generateThumbnail(File $sourceFile, $thumbnailFilename, $width = 500, $height = 500, $format = 'png')
    {
        $address = $this->getAddressFromURIList($sourceFile);

        $urlValidator = new Url();
        $urlValidator->message = 'Wrong URL format for Thumbnail generation in mimetype "text/uri-list".';

        // Use the validator to validate the value
        $errorList = $this->validator->validate($address, $urlValidator);
        if ($errorList->count()) {
            $validation = $errorList->get(0);
            throw new BadRequestHttpException($validation->getMessage());
        }

        $this->handleThumbnailGeneration($address, $thumbnailFilename, $format, $width, $height);
    }

    /**
     * Handles PhantomJS invocation, responses and thumbnail generation.
     *
     * @param string $address           The URL File to create the thumbnail from
     * @param string $thumbnailFilename The filename where to store the generated thumbnail
     * @param string $format            The thumbnail image format
     * @param int    $width             The thumbnail width
     * @param int    $height            The thumbnail height
     * @param int $recursionCount       The current redirect count
     *
     * @return ResponseInterface
     */
    private function handleThumbnailGeneration($address, $thumbnailFilename, $format, $width, $height, $recursionCount = 0)
    {
        if ($recursionCount > $this->maxRedirects) {
          $this->logger->error('Error while generating WEB thumbnail from "{url}" with PhantomJS, too many redirects', [
              'url' => $address,
          ]);
          throw new GatewayMaxRedirectsHttpException('Max redirect count while handling "'.$address.'" thumbnail (redirect count: '.$recursionCount.')');
        }

        $resp = $this->getPhantomJSResponse($address, $thumbnailFilename, $format, $width, $height);

        switch ($resp->getStatus()) {
          case 200:
              // We're good, return the current response
              return $resp;
          case 301: // HTTP_REDIRECT_PERM
          case 302: // HTTP_REDIRECT_FOUND:
          case 307: // HTTP_REDIRECT_TEMP:
              // Redirect has been requested, send the new request
              $newAddress = $resp->getRedirectUrl();
              $this->logger->info('Redirect while generating WEB thumbnail from "{url}" with PhantomJS, new url "{new-url}"', [
                  'url' => $address,
                  'new-url' => $newAddress,
              ]);

              return $this->handleThumbnailGeneration($newAddress, $thumbnailFilename, $format, $width, $height, $recursionCount+1);
          case 408: // GatewayTimeout
              // Timeout occurred, throw an exception
              $this->logPhantomJSResponseError($address, $resp);
              // Throw a 504 exception
              throw new GatewayTimeoutHttpException('Timeout during the generation of "'.$address.'" thumbnail (error: '.$resp->getStatus().')');
          default:
              $this->logPhantomJSResponseError($address, $resp);
              // Throw a 502 exception
              throw new BadGatewayHttpException('Unknown response from "'.$address.'" (code: '.$resp->getStatus().')');
        }
    }

    /**
     * Log a wrong response received from PhantomJS.
     *
     * @param string            $address The URL address
     * @param ResponseInterface $resp    The PhantomJS response
     */
    private function logPhantomJSResponseError($address, $resp)
    {
        $this->logger->error('Error while generating WEB thumbnail from "{url}" with PhantomJS, response code: {code}', [
            'url' => $address,
            'code' => $resp->getStatus(),
            'time' => $resp->getTime(),
            'content-type' => $resp->getContentType(),
        ]);
    }

    /**
     * Invoke PhantomJS and return the response.
     *
     * @param string $address           The URL File to create the thumbnail from
     * @param string $thumbnailFilename The filename where to store the generated thumbnail
     * @param int    $width             The thumbnail width
     * @param int    $height            The thumbnail height
     * @param string $format            The thumbnail image format
     *
     * @return ResponseInterface
     */
    private function getPhantomJSResponse($address, $thumbnailFilename, $format, $width, $height)
    {
        $this->logger->info('Generating WEB thumbnail from "{url}" with PhantomJS', [
            'url' => $address,
            'thumbnailFilename' => $thumbnailFilename,
            'format' => $format,
        ]);

        /** @var \JonnyW\PhantomJs\Http\CaptureRequest $request */
        $request = $this->phantomJsClient->getMessageFactory()->createCaptureRequest($address, 'GET', $this->timeout);

        $request->setOutputFile($thumbnailFilename);
        $request->setViewportSize($width, $height);
        $request->setCaptureDimensions($width, $height, 0, 0);
        $request->setFormat($format);

        $response = $this->phantomJsClient->getMessageFactory()->createResponse();

        return $this->phantomJsClient->send($request, $response);
    }

    /**
     * @param File $file
     *
     * @throws BadRequestHttpException
     *
     * @return string
     */
    private function getAddressFromURIList(File $file)
    {
        $fileObj = new \SplFileObject($file->getRealPath());

        $address = [];

        // Loop until we reach the end of the file.
        while (!$fileObj->eof()) {
            $line = trim($fileObj->fgets());
            if (!$line || 0 === strpos($line, '#')) {
                continue;
            }

            if (count($address)) {
                $address = null;
                break;
            }

            $address[] = $line;
        }
        $fileObj = null;

        if (1 !== count($address)) {
            $this->logger->warning('Trying to generate a WEB thumbnail, wrong URIs', [
                'contents' => file_get_contents($file->getRealPath()),
            ]);
            throw new BadRequestHttpException('Wrong URI format, only one URI is supported for uri-list document');
        }

        return reset($address);
    }
}
