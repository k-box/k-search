<?php

namespace App\Model\Search;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="SearchAggregationRequest"
 * )
 */
class SearchAggregationRequest
{
    /**
     * Aggregation name, usually a Data field name.
     *
     * @var string
     * @SWG\Property()
     */
    public $name;

    /**
     * Only retrieve the given amount of aggregated values.
     *
     * @var int
     * @SWG\Property()
     */
    public $limit;

    /**
     * Calculate aggregations count after applying filters (True, default) or before (False).
     *
     * @var bool
     * @SWG\Property()
     */
    public $counts_filtered;
}
