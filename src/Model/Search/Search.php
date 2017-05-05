<?php

namespace App\Model\Search;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Search",
 *     required={"query"}
 * )
 */
class Search
{
    /**
     * The complete SearchQuery object from the request.
     *
     * @var SearchRequest
     * @SWG\Property()
     */
    public $query;

    /**
     * @var SearchAggregation[]
     * @SWG\Property()
     */
    public $aggregations;

    /**
     * An object holding the result from the search.
     *
     * @var SearchItem[]
     *
     * @SWG\Property()
     */
    public $results = [];
}
