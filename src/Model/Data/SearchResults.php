<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\SearchResults",
 *     required={"query","query_time","total_matches"}
 * )
 */
class SearchResults
{
    /**
     * The complete SearchQuery object from the request.
     *
     * @var \App\Model\Data\SearchParams
     * @JMS\Type("App\Model\Data\SearchParams")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $query;

    /**
     * The time needed to run the search query.
     *
     * @var int
     * @JMS\Type("integer")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="104"
     * )
     */
    public $query_time;

    /**
     * The total amount of found items.
     *
     * @var int
     * @JMS\Type("integer")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="1"
     * )
     */
    public $total_matches;

    /**
     * Array of aggregations.
     *
     * @Assert\Valid()
     * @JMS\Type("array<string,array<App\Model\Data\AggregationResult>>")
     * @SWG\Property()
     *
     * @var array
     */
    public $aggregations;

    /**
     * Array of results.
     *
     * @var Data[]
     * @Assert\Valid()
     * @JMS\Type("array<App\Model\Data\Data>")
     * @SWG\Property()
     */
    public $items;
}
