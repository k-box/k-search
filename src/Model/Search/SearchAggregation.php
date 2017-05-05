<?php

namespace App\Model\Search;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition()
 */
class SearchAggregation
{
    /**
     * Aggregation name, usually a Data field name.
     *
     * @var string
     * @SWG\Property()
     */
    public $name;

    /**
     * Count of the results according to aggregations.
     *
     * @var int
     * @SWG\Property()
     */
    public $count;
}
