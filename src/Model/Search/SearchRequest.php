<?php

namespace App\Model\Search;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="SearchRequest",
 *     required={"search"}
 * )
 */
class SearchRequest
{
    /**
     * String of the search query. If no query is specified, an empty result set will be returned.
     *
     * @var string
     * @SWG\Property()
     */
    public $search;

    /**
     * Search filters in the Lucene query parser syntax.
     *
     * @var string
     * @SWG\Property()
     */
    public $filters;

    /**
     * The number of items to return from the search.
     *
     * @var int
     * @SWG\Property(
     *     default="10"
     * )
     */
    public $limit;

    /**
     * The offset of the items to return.
     *
     * @var int
     * @SWG\Property(
     *     default="0"
     * )
     */
    public $offset;

    /**
     * @var SearchAggregationRequest[]
     * @SWG\Property()
     */
    public $aggregations;
}
