<?php

namespace App\Model\Data\Search;

use App\Validator\Constraints\ValidGeoLocationFilter;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Search\GeoLocationFilter",
 *     required={"bounding_box"}
 * )
 *
 * @ValidGeoLocationFilter()
 */
class GeoLocationFilter
{
    /**
     * The bounding box of the geo-location, as an escaped GeoJson string. Only a Polygon is currently supported.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\Since("3.5")
     * @JMS\SerializedName("bounding_box")
     * @Assert\NotBlank()
     *
     * @SWG\Property(
     *     property="bounding_box",
     *     example="{""type"": ""Polygon"", ""coordinates"": [[[10.0, 10.0], [10.0, 20.0], [20.0, 20.0], [20.0, 10.0], [10.0, 10.0]]] }",
     *     x={"since-version":"3.5"},
     * )
     */
    public $boundingBox;
}
