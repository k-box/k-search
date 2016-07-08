<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 11/03/2015
 * Time: 22:16
 */

namespace KCore\ThumbnailsAPIBundle\Library;


use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use JonnyW\PhantomJs\Client;

class ThumbnailFromWebpage {

    protected $phantomDir = null;
    protected $width;
    protected $height;


    /**
     * @param $phantomDir
     * @param int $width
     * @param int $height
     */
    function __construct($phantomDir, $width = 500, $height = 376)
    {
        $this->phantomDir = $phantomDir;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param File $queueFile
     * @param string $partFile
     */
    public function generateThumbnail(File $queueFile, $partFile) {

        $address = $this->readAddressFromURIList($queueFile);

        $client = Client::getInstance();
        $client->setBinDir($this->phantomDir);
        $request = $client->getMessageFactory()->createCaptureRequest($address, 'GET');
        $request->setCaptureFile($partFile);

        $request->setViewportSize($this->width, $this->height);
        $request->setCaptureDimensions($this->width, $this->height, 0, 0);

        $response = $client->getMessageFactory()->createResponse();

        $client->send($request, $response);
    }

    /**
     * @param $uriFile
     * @return null|string
     * @throws BadRequestHttpException
     */
    private function readAddressFromURIList(File $uriFile) {
        $address = NULL;
        $file = fopen($uriFile->getRealPath(), "r");
        while(!feof($file)){
            $line = fgets($file);
            if ($line && strlen($line) > 0 && $line[0] != "#") {
                if ($address == NULL) {
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
