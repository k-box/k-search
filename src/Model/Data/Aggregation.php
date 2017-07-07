<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Aggregation",
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
     *     maximum=100,
     *     minimum=1,
     *     default=10,
     * )
     */
    public $limit = 10;

    /**
     * Calculate aggregations count after applying filters (True) or before (False).
     *
     * @var bool
     * @JMS\Type("boolean")
     * @SWG\Property(
     *     type="boolean",
     *     default=false,
     * )
     */
    public $countsFiltered = false;
}
