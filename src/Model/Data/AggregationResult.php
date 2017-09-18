<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\AggregationResult",
 *     required={"name", "data"}
 * )
 */
class AggregationResult
{
    /**
     * The aggregation name.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="language"
     * )
     */
    public $name;

    /**
     * The aggregation result.
     *
     * @var array
     * @Assert\NotBlank()
     * @JMS\Type("array<App\Model\Data\AggregationItem>")
     * @SWG\Property()
     */
    public $data;
}
