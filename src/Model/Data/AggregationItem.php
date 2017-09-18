<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\AggregationItem",
 *     required={"value", "count"}
 * )
 */
class AggregationItem
{
    /**
     * The aggregation value.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="es"
     * )
     */
    public $value;

    /**
     * How many items in aggregation.
     *
     * @var int
     * @Assert\NotBlank()
     * @JMS\Type("integer")
     * @SWG\Property(
     *     example="102"
     * )
     */
    public $count;
}
