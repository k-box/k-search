<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 15/05/2015
 * Time: 16:35.
 */
namespace KCore\MonitorBundle\Check;

use KCore\CoreBundle\Services\CoreService;
use Solarium\QueryType\Collections\QueryHelper;
use Solarium\QueryType\Collections\Result;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;

class NetworkStatusCheck extends BaseSolrCheck
{
    public function __construct(CoreService $coreService)
    {
        parent::__construct($coreService);
    }

    /**
     * Perform the actual check and return a ResultInterface.
     *
     * @return ResultInterface
     */
    public function check()
    {
        $client = $this->coreService->getPublicSolrClient();

        QueryHelper::registerCollectionsAPI($client);
        $query = QueryHelper::getQueryStatusQuery($client);
        $query->getCommand()->setCollection('klink-public');

        /** @var Result $result */
        $result = QueryHelper::execute($client, $query);

        $nodes = $result->returnProperty('cluster')->live_nodes;
        if (count($nodes) > 0) {
            array_walk($nodes, function (&$item, $key) {
                $item = current(explode(':', $item));
            });

            return new Success(sprintf('KLink network is running on %d nodes (%s)', count($nodes), implode(', ', $nodes)));
        }

        return new Failure('Error checking the KLink network!');
    }

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel()
    {
        return 'KLink network status';
    }
}
