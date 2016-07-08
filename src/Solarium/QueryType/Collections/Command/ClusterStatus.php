<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 15/05/2015
 * Time: 16:56
 */

namespace Solarium\QueryType\Collections\Command;

use Solarium\Core\Client\Request;
use Solarium\Core\Query\Result\Result;
use Solarium\QueryType\Collections\Query;

class ClusterStatus extends Command {

    protected $shard = null;
    protected $collection = null;

    /**
     * Returns command type, for use in adapters
     *
     * @return string
     */
    public function getType() {
        return Query::COMMAND_CLUSTER_STATUS;
    }

    public function getAction() {
        return 'CLUSTERSTATUS';
    }

    public function buildRequest(Query $query, Request $request) {
        if ($this->getCollection()) {
            $request->addParam('collection', $this->getCollection());
        }

        if ($this->getShard()) {
            $request->addParam('shard', $this->getShard());
        }
    }

    /**
     * @return null
     */
    public function getShard() {
        return $this->shard;
    }

    /**
     * @param null $shard
     */
    public function setShard($shard) {
        $this->shard = $shard;
    }

    /**
     * @return null
     */
    public function getCollection() {
        return $this->collection;
    }

    /**
     * @param null $collection
     */
    public function setCollection($collection) {
        $this->collection = $collection;
    }

    /**
     * @param Result $result
     * @return mixed
     */
    public function parse(Result $result) {
        $data = $result->getData();
        return array('cluster' => (object) $data['cluster']);
    }
}