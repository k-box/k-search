<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 15/05/2015
 * Time: 15:20.
 */
namespace KCore\MonitorBundle\Check;

use KCore\CoreBundle\Services\CoreService;
use Solarium\Core\Client\Client;
use ZendDiagnostics\Check\CheckInterface;

abstract class BaseSolrCheck implements CheckInterface
{
    /** @var CoreService $coreService */
    protected $coreService;

    public function __construct(CoreService $coreService)
    {
        $this->coreService = $coreService;
    }

    /**
     * @param \Solarium\Core\Client\Client $client
     *
     * @return bool
     */
    protected function checkPing(Client $client)
    {
        $ping = $client->createPing();
        $result = $client->execute($ping);

        return $result->getResponse()->getStatusCode() == 200;
    }
}
