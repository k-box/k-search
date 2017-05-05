<?php

namespace App\Helper;

use App\Entity\DocumentDescriptor;
use App\Services\CoreService;

class SearchHelper
{
    /**
     * Returns the Solarium Client given the core name.
     *
     * @param string $coreName
     *
     * @return string|null
     */
    public static function getVisibilityByCore($coreName)
    {
        switch ($coreName) {
            case 'private':
                return DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE;
                break;
            case 'public':
                return DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC;
                break;
        }

        return null;
    }

    /**
     * Returns the Solarium Client given the core name.
     *
     * @param CoreService $coreService The KSearch service
     * @param string      $coreName    The Core name
     *
     * @return null|\Solarium\Client
     */
    public static function getClientByCoreName(CoreService $coreService, $coreName)
    {
        switch ($coreName) {
            case 'private':
                $client = $coreService->getPrivateSolrClient();
                break;
            case 'public':
                $client = $coreService->getPublicSolrClient();
                break;
            default:
                $client = null;
        }

        return $client;
    }
}
