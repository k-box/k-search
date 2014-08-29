<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 10/09/2014
 * Time: 18:54
 */

namespace KCore\CoreBundle\Services;

use Nelmio\SolariumBundle\ClientRegistry;

class CoreService {
    const KCORE_ID_SEPARATOR    = ':';

    /** @var string $institutionId */
    protected $institutionId;
    protected $globalClientName;
    protected $localClientName;

    /** @var  ClientRegistry $clientRegistry */
    protected $clientRegistry;

    /**
     * @param ClientRegistry $clientRegistry
     * @param $institutionId
     */
    function __construct(ClientRegistry $clientRegistry, $institutionId, array $clients) {
        $this->clientRegistry = $clientRegistry;
        $this->institutionId = $institutionId;
        $this->localClientName =  $clients['local'];
        $this->globalClientName = $clients['global'];
    }

    /**
     * Returns the Local search Solarium Client
     * @return \Solarium\Client
     */
    function getPrivateSolrClient() {
        return $this->clientRegistry->getClient($this->localClientName);
    }

    /**
     * Returns the Global search Solarium Client
     * @return \Solarium\Client
     */
    function getPublicSolrClient() {
        return $this->clientRegistry->getClient($this->globalClientName);
    }

    /**
     * @return string
     */
    function getInstitutionId() {
        return $this->institutionId;
    }




} 