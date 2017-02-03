<?php

namespace Solarium\QueryType\Collections;

use Solarium\Core\Query\ResponseParser as ResponseParserAbstract;
use Solarium\Core\Query\ResponseParserInterface as ResponseParserInterface;

class ResponseParser extends ResponseParserAbstract implements ResponseParserInterface
{
    /**
     * Implements \Solarium\Core\Query\ResponseParserInterface::parse().
     */
    public function parse($result)
    {

        /** @var Query $query */
        $query = $result->getQuery();

        $data = $query->getCommand()->parse($result);

        return $this->addHeaderInfo($result->getData(), $data);
    }
}
