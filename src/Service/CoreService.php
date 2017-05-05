<?php

namespace App\Service;

use Nelmio\SolariumBundle\ClientRegistry;

class CoreService
{
    const ID_SEPARATOR = ':';
    const TYPE_PUBLIC = 'public';
    const TYPE_PRIVATE = 'private';

    /** @var string $institutionId */
    protected $institutionId;

    protected $coreType;

    /** @var ClientRegistry $clientRegistry */
    protected $clientRegistry;

    /**
     * @param ClientRegistry $clientRegistry The Solarium Client library
     * @param string         $institutionId  The Institution ID
     * @param string         $coreType       The core type, used to define the document visibility
     */
    public function __construct(ClientRegistry $clientRegistry, $institutionId, $coreType)
    {
        $this->clientRegistry = $clientRegistry;
        $this->institutionId = $institutionId;
        $this->coreType = $coreType;
    }

    /**
     * Returns the Local search Solarium Client.
     *
     * @return \Solarium\Client
     */
    public function getPrivateSolrClient()
    {
        return $this->getCoreClientByType(self::TYPE_PRIVATE);
    }

    /**
     * Returns the Global search Solarium Client.
     *
     * @return \Solarium\Client
     */
    public function getPublicSolrClient()
    {
        return $this->getCoreClientByType(self::TYPE_PUBLIC);
    }

    /**
     * Returns the Core type: public or private.
     *
     * @return string
     */
    public function getCoreType()
    {
        return $this->coreType;
    }

    /**
     * @return string
     */
    public function getInstitutionId()
    {
        return $this->institutionId;
    }

    /**
     * @return \Solarium\Client
     */
    public function getCoreClient()
    {
        return $this->clientRegistry->getClient();
    }

    /**
     * @param string $type
     *
     * @return \Solarium\Client
     */
    public function getCoreClientByType($type)
    {
        if ($type !== $this->coreType) {
            throw new WrongCoreException('Invalid KSearch configuration: this is not a "'.$type.'" Core');
        }

        return $this->clientRegistry->getClient();
    }
}
