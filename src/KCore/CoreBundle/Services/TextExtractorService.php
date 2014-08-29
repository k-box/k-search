<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 03/11/2014
 * Time: 21:39
 */

namespace KCore\CoreBundle\Services;

use Symfony\Component\HttpFoundation\File\File;

class TextExtractorService {

    /** @var  CoreService */
    protected $coreService;

    /**
     * @param CoreService $coreService
     */
    function __construct(CoreService $coreService) {
        $this->coreService = $coreService;
    }


    /**
     * @param File $file
     * @param bool $publicDocument
     * @throws \Exception
     * @return string
     */
    function extractText(File $file, $publicDocument = true) {
        if (!$file->isFile() || !$file->isReadable())
            return null;

        $client = ($publicDocument) ? $this->coreService->getPublicSolrClient() : $this->coreService->getPrivateSolrClient();

        $extractor = $client->createExtract();
        $extractor->setOmitHeader(true);
        $extractor->setCommit(false);

        $extractor->setFile($file->getRealPath());
        $extractor->addParam('extractFormat', 'text');
        $extractor->addParam('extractOnly', 'true');
        $result = $client->extract($extractor);

        if($result->getResponse()->getStatusCode() != 200) {
            throw new \Exception($result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode());
        }

        $json = json_decode($result->getResponse()->getBody());
        return $json;
    }
} 