<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

class TextExtractorService
{
    /** @var CoreService */
    protected $coreService;

    /**
     * @param CoreService $coreService
     */
    public function __construct(CoreService $coreService)
    {
        $this->coreService = $coreService;
    }

    /**
     * Extract text from a given file and returns the text as a string.
     *
     * @param File $file           The file to extract the text from
     * @param bool $publicDocument Flag for public documents
     *
     * @throws \Exception
     *
     * @return string
     */
    public function extractText(File $file, $publicDocument = true)
    {
        if (!$file->isFile() || !$file->isReadable()) {
            return null;
        }

        $client = ($publicDocument) ? $this->coreService->getPublicSolrClient() : $this->coreService->getPrivateSolrClient();

        $extractor = $client->createExtract();
        $extractor->setOmitHeader(true);
        $extractor->setCommit(false);

        $extractor->setFile($file->getRealPath());
        $extractor->addParam('extractFormat', 'text');
        $extractor->addParam('captureAttr', false);
        $extractor->setExtractOnly(true);

        $result = $client->extract($extractor);

        if ((int) $result->getResponse()->getStatusCode() !== 200) {
            throw new \Exception($result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode());
        }

        $json = json_decode($result->getResponse()->getBody(), true);

        $filename = addslashes($file->getPathname());
        if (!array_key_exists($filename, $json)) {
            return null;
        }

        return $json[$filename];
    }
}
