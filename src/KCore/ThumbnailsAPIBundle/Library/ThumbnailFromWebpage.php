<?php

namespace KCore\ThumbnailsAPIBundle\Library;

use JonnyW\PhantomJs\Client;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ThumbnailFromWebpage
{
    protected $phantomDir = null;
    protected $width;
    protected $height;

    /**
     * @param int $width
     * @param int $height
     */
    public function __construct($width = 500, $height = 376)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param File   $queueFile
     * @param string $partFile
     */
    public function generateThumbnail(File $queueFile, $partFile)
    {
        $address = $this->readAddressFromURIList($queueFile);

        $client = Client::getInstance();
        /** @var \JonnyW\PhantomJs\Http\CaptureRequest $request */
        $request = $client->getMessageFactory()->createCaptureRequest($address, 'GET');

        $request->setOutputFile($partFile);
        $request->setViewportSize($this->width, $this->height);
        $request->setCaptureDimensions($this->width, $this->height, 0, 0);

        $response = $client->getMessageFactory()->createResponse();

        $client->send($request, $response);
    }

    /**
     * @param $uriFile
     *
     * @throws BadRequestHttpException
     *
     * @return null|string
     */
    private function readAddressFromURIList(File $uriFile)
    {
        $address = null;
        $file = fopen($uriFile->getRealPath(), 'r');
        while (!feof($file)) {
            $line = fgets($file);
            if ($line && strlen($line) > 0 && $line[0] != '#') {
                if ($address == null) {
                    $address = trim($line);
                } else {
                    throw new BadRequestHttpException('Only one URI is supported for uri-list document');
                }
            }
        }
        fclose($file);

        return $address;
    }
}
