<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties",
 *     description="The properties of the data.",
 *     required={"title", "filename",}
 * )
 */
class Properties
{
    /**
     * The data title.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="Adventures of Sherlock Holmes",
     * )
     */
    public $title;

    /**
     * The file name of the data.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="adventures-of-sherlock-holmes.pdf",
     * )
     */
    public $filename;

    /**
     * The Mime type of the provided data.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     property="mime_type",
     *     example="application/pdf",
     * )
     */
    public $mimeType;

    /**
     * ISO code of the main language (explicitly the abstract and title).
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min="2", max="2")
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="en",
     *     maxLength=2,
     *     minLength=2,
     * )
     */
    public $language;

    /**
     * Data’s or document’s creation date in [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) format.
     *
     * @var \DateTimeInterface
     * @Assert\NotBlank()
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s\Z', 'UTC'>")
     * @SWG\Property(
     *     property="created_at",
     *     example="2008-07-28T14:47:31Z",
     * )
     */
    public $createdAt;

    /**
     * Data’s or document’s updated date in [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) format.
     *
     * @var \DateTimeInterface
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s\Z', 'UTC'>")
     * @SWG\Property(
     *     property="updated_at",
     *     example="2008-09-28T15:47:31Z",
     * )
     */
    public $updatedAt;

    /**
     * The file size of the data, in bytes.
     *
     * @var int
     * @JMS\Type("integer")
     * @SWG\Property(
     *     example="717590",
     * )
     */
    public $size;

    /**
     * A short abstract about the data or document.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="This is an example of abstract",
     * )
     */
    public $abstract;

    /**
     * The URI where the a thumbnail of this data is stored.
     *
     * @var string
     * @Assert\Url()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="http://thumbnailserver.com/get/thumbnail123"
     * )
     */
    public $thumbnail;

    /**
     * User-definet tags associated to the data (multiple).
     *
     * @var array
     * @Assert\Type("array")
     * @JMS\Type("array<string>")
     * @SWG\Property(
     *     @SWG\Items(type="string"),
     *     example={"tag1", "tag2"},
     * )
     */
    public $tags;

    /**
     * Search data and browse within the hierarchy (multiple).
     * However be careful to expose the hierarchy to a public search index, as it may contain confidential data.
     *
     * @var array
     * @Assert\Type("array")
     * @JMS\Type("array<string>")
     * @SWG\Property(
     *     @SWG\Items(type="string"),
     *     example={"COLLECTION_ID_1", "COLLECTION_ID_2"}
     * )
     */
    public $collections;

    /**
     * Object containing information on the video file.
     *
     * @var Properties\Video
     * @Assert\Callback({"App\Validation\AvailableOnlyForVideo", "validate"})
     * @Assert\Callback({"App\Validation\RequiredOnlyForVideo", "validate"})
     * @Assert\Valid()
     * @JMS\Type("App\Model\Data\Properties\Video")
     * @SWG\Property()
     */
    public $video;

    /**
     * Audio tracks attached to the video (multiple).
     *
     * @var Properties\Audio[]
     * @Assert\Callback({"App\Validation\AvailableOnlyForVideo", "validate"})
     * @JMS\Type("array<App\Model\Data\Properties\Audio>")
     * @SWG\Property()
     */
    public $audio;

    /**
     * Subtitles attached to the video (multiple).
     *
     * @var Properties\Subtitles[]
     * @Assert\Callback({"App\Validation\AvailableOnlyForVideo", "validate"})
     * @JMS\Type("array<App\Model\Data\Properties\Subtitles>")
     * @SWG\Property()
     */
    public $subtitles;
}
