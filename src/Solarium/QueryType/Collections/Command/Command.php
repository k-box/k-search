<?php

namespace Solarium\QueryType\Collections\Command;

use Solarium\Core\Client\Request;
use Solarium\Core\Configurable;
use Solarium\Core\Query\Result\Result;
use Solarium\QueryType\Collections\Query;

/**
 * Update query command base class.
 */
abstract class Command extends Configurable
{
    /**
     * Returns command type, for use in adapters.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * @return string
     */
    abstract public function getAction();

    /**
     * @param Query   $query
     * @param Request $request
     *
     * @return mixed
     */
    abstract public function buildRequest(Query $query, Request $request);

    /**
     * @param Result $result
     *
     * @return mixed
     */
    abstract public function parse(Result $result);
}
