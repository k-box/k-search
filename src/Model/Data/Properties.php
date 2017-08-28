<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties",
 *     description="The metadata of the data.",
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
     *     example="application/pdf",
     * )
     */
    public $mime_type;

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
     * @var \DateTime
     * @Assert\NotBlank()
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s\Z', 'UTC'>")
     * @SWG\Property(
     *     example="2008-07-28T14:47:31Z",
     * )
     */
    public $created_at;

    /**
     * Data’s or document’s updated date in [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) format.
     *
     * @var \DateTime
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s\Z', 'UTC'>")
     * @SWG\Property(
     *     example="2008-09-28T15:47:31Z",
     * )
     */
    public $updated_at;

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
     */
    public $abstract;

    /**
     * The URI where the a thumbnail of this data is stored.
     *
     * @var string
     * @Assert\Url()
     * @JMS\Type("string")
     */
    public $thumbnail;
}
