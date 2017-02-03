<?php

namespace KCore\CoreBundle\Libraries;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Services\CoreService;

class KCoreHelper
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
     * @param CoreService $coreService The KCore service
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
