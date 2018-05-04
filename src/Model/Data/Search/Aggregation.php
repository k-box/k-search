<?php

namespace App\Model\Data\Search;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Search\Aggregation",
 *     required={""}
 * )
 */
class Aggregation
{
    /**
     * Only retrieve a certain amount of the most common aggregations.
     *
     * @var int
     * @JMS\Type("integer")
     * @Assert\Type("integer")
     * @Assert\Range(
     *     min=1,
     *     max=100,
     * )
     * @SWG\Property(
     *     type="integer",
     *     default=10,
     *     maximum=100,
     *     minimum=1,
     * )
     */
    public $limit = 10;

    /**
     * Calculate aggregations count after applying filters (True) or before (False).
     *
     * @var bool
     * @JMS\Type("boolean")
     * @Assert\Type("boolean")
     * @SWG\Property(
     *     type="boolean",
     *     default=false,
     * )
     */
    public $countsFiltered = false;

    /**
     * Return only aggregations having at least min-count items.
     * Note: the default value on API previous v3.2 is 0.
     *
     * @var int
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual(0)
     * @JMS\Type("integer")
     * @JMS\Since("3.2")
     * @SWG\Property(
     *     type="integer",
     *     default=1,
     *     minimum=0,
     *     x={"since-version":"3.2"},
     * )
     */
    public $minCount = 1;
}
