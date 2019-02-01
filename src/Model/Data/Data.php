<?php

namespace App\Model\Data;

use App\Validator\Constraints\ValidGeoLocation;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Data",
 *     required={"uuid", "hash", "type", "url", "author", "copyright", "uploader", "properties"}
 * )
 */
class Data
{
    public const DATA_TYPE_DOCUMENT = 'document';
    public const DATA_TYPE_VIDEO = 'video';

    /**
     * The Universally unique identifier of this data.
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Uuid()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd",
     * )
     */
    public $uuid;

    /**
     * The URI where the source data is stored and retrievable.
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Url()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="http://publicliterature.org/pdf/advsh12.pdf",
     * )
     */
    public $url;

    /**
     * The SHA-2 hash of the Document contents (SHA-512, thus 128 Chars).
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min="128", max="128")
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="d6f644b19812e97b5d871658d6d3400ecd4787faeb9b8990c1e7608288664be77257104a58d033bcf1a0e0945ff06468ebe53e2dff36e248424c7273117dac09",
     * )
     */
    public $hash;

    /**
     * The general type of the provided data.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @Assert\Choice(
     *     strict=true,
     *     choices={"document", "video"}
     * )
     * @SWG\Property(
     *     enum={"document", "video"},
     *     example="video"
     * )
     */
    public $type;

    /**
     * The general type of the provided data.
     *
     * @var Klink[]
     * @JMS\ReadOnly()
     * @JMS\Type("array<App\Model\Data\Klink>")
     * @JMS\Since("3.7")
     * @SWG\Property(
     *     x={"since-version":"3.7"},
     * )
     */
    public $klinks = [];

    /**
     * The identifiers of the K-Links to which this data is published.
     *
     * This data is for internal use only, not exposed by default.
     *
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"details"})
     * @JMS\Since("3.7")
     */
    public $klink_ids = [];

    /**
     * The properties of the data.
     *
     * @var Properties
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @JMS\Type("App\Model\Data\Properties")
     * @SWG\Property()
     */
    public $properties;

    /**
     * List of authors (multiple).
     *
     * @var Author[]
     * @Assert\Type("array")
     * @Assert\Valid()
     * @JMS\Type("array<App\Model\Data\Author>")
     * @SWG\Property()
     */
    public $authors;

    /**
     * Information on the copyright.
     *
     * @var Copyright
     * @Assert\Valid()
     * @JMS\Type("App\Model\Data\Copyright")
     * @SWG\Property()
     */
    public $copyright;

    /**
     * The originating source where the data has been uploaded or created.
     *
     * @var Uploader
     * @Assert\Valid()
     * @JMS\Type("App\Model\Data\Uploader")
     * @SWG\Property()
     */
    public $uploader;

    /**
     * The Geo location of the data, as an escaped GeoJson string.
     *
     * The coordinates must be in the WGS84 coordinate system.
     * The order of the coordinates must be longitude, latitude
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\Since("3.5")
     * @ValidGeoLocation()
     * @SWG\Property(
     *     property="geo_location",
     *     example="{""type"": ""Point"", ""coordinates"": [100.0, 0.0] }",
     *     x={"since-version":"3.5"},
     * )
     */
    public $geoLocation;

    /**
     * The status of the data, internal use only, not exposed.
     *
     * @var string
     * @JMS\Groups({"details"})
     */
    public $status;

    /**
     * The error status of the data, internal use only, not exposed.
     *
     * @var string|null
     * @JMS\Groups({"details"})
     */
    public $errorStatus;

    /**
     * The requestId that triggered the data, internal use only, not exposed.
     *
     * @var string
     * @JMS\Groups({"details"})
     */
    public $requestId;

    /**
     * The datetime when the data was updated, internal use only, not exposed.
     *
     * @var \DateTimeInterface
     * @JMS\Groups({"details"})
     */
    public $updatedAt;
}
