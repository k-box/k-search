<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Data",
 *     required={"uuid", "hash", "type", "url"}
 * )
 */
class Data
{
    public const DATA_STATUS_QUEUED = 'queued';
    public const DATA_STATUS_OK = 'ok';

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
     * @Assert\NotNull()
     * @JMS\Type("string")
     * @Assert\Choice(
     *     strict=true,
     *     choices={"document", "video"}
     * )
     * @SWG\Property(
     *     enum={"document", "video"}
     * )
     */
    public $type;

    /**
     * Information on the copyright.
     *
     * @var Copyright
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @JMS\Type("App\Model\Data\Copyright")
     * @SWG\Property()
     */
    public $copyright;

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
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @JMS\Type("array<App\Model\Data\Author>)
     * @SWG\Property()
     */
    public $author;

    /**
     * The status of the data, internal use only, not exposed.
     *
     * @var string
     */
    public $status;
}
