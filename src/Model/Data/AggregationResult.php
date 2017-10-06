<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\AggregationResult",
 *     required={"value", "count"}
 * )
 */
class AggregationResult
{
    /**
     * The aggregation value.
     *
     * @var string
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
     * @JMS\ReadOnly()
     * @JMS\Type("integer")
     * @SWG\Property(
     *     example="102"
     * )
     */
    public $count;

    public function __construct(string $value, int $count)
    {
        $this->value = $value;
        $this->count = $count;
    }
}
