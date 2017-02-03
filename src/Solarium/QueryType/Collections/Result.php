<?php

namespace Solarium\QueryType\Collections;

use Solarium\Core\Query\Result\QueryType as BaseResult;

/**
 * Result object for admin/system queries.
 */
class Result extends BaseResult
{
    /**
     * Status code returned by Solr.
     *
     * @var int
     */
    protected $status;

    /**
     * Solr index queryTime.
     *
     * This does not include things like the HTTP response-time. Purely the Solr
     * query execution time.
     *
     * @var int
     */
    protected $queryTime;

    /**
     * Ensures the response is parsed and returns a property.
     *
     * @param string $property
     *                         The name of the class member variable
     *
     * @return mixed
     *               The value of the property
     */
    public function returnProperty($property)
    {
        $this->parseResponse();

        return $this->$property;
    }

    /**
     * Get Solr status code.
     *
     * This is not the HTTP status code! The normal value for success is 0.
     *
     * @return int
     */
    public function getStatus()
    {
        $this->parseResponse();

        return $this->status;
    }
}
