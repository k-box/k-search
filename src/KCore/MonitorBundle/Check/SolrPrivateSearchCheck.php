<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 15/05/2015
 * Time: 15:19
 */

namespace KCore\MonitorBundle\Check;


use KCore\CoreBundle\Services\CoreService;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;

class SolrPrivateSearchCheck extends BaseSolrCheck {


    /**
     * @param \KCore\CoreBundle\Services\CoreService $coreService
     */
    function __construct(CoreService $coreService) {
        parent::__construct($coreService);
    }

    /**
     * Perform the actual check and return a ResultInterface
     *
     * @return ResultInterface
     */
    public function check() {

        if ($this->checkPing($this->coreService->getPrivateSolrClient())) {
            return new Success('SolrPrivate is running');
        }
        else {
            return new Failure('SolrPrivate is not running, no answer on ping');
        }
    }

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel() {
        return 'KCore Private Search';
    }
}