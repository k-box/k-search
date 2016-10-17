<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 15/05/2015
 * Time: 16:48.
 */
namespace Solarium\QueryType\Collections;

use Solarium\Core\Client\Request;
use Solarium\Core\Query\QueryInterface;
use Solarium\Core\Query\RequestBuilder as BaseRequestBuilder;

/**
 * Build a System request.
 */
class RequestBuilder extends BaseRequestBuilder
{
    /**
     * Overrides \Solarium\Core\Query\RequestBuilder::build().
     */
    public function build(QueryInterface $query)
    {
        $request = parent::build($query);
        $request->setMethod(Request::METHOD_GET);
        $request->addParam('omitHeader', 'true');

        // Build the complete command request
        $this->buildCommandRequest($query, $request);

        return $request;
    }

    /**
     * @param \Solarium\QueryType\Collections\Query $query
     * @param \Solarium\Core\Client\Request         $request
     */
    public function buildCommandRequest(Query $query, Request $request)
    {
        // Adding command Action
        $request->addParam('action', $query->getCommand()->getAction());

        $query->getCommand()->buildRequest($query, $request);
    }
}
