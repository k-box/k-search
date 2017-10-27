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
     * @var SearchParams
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
     *     property="query_time",
     *     example="104",
     * )
     */
    public $queryTime;

    /**
     * The total amount of found items.
     *
     * @var int
     * @JMS\Type("integer")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     property="total_matches",
     *     example="1",
     * )
     */
    public $totalMatches;

    /**
     * Array of aggregations.
     *
     * @var array
     * @Assert\Valid()
     * @JMS\Type("array<string,array<App\Model\Data\AggregationResult>>")
     * @SWG\Property(
     *     @SWG\Items(
     *        type="array",
     *        @SWG\Items(ref="#/definitions/Data\AggregationResult")
     *     ),
     * )
     */
    public $aggregations;

    /**
     * Array of results.
     *
     * @var Data[]
     * @JMS\Type("array<App\Model\Data\Data>")
     * @SWG\Property()
     */
    public $items;

    public function __construct(SearchParams $query)
    {
        $this->query = $query;
    }
}
