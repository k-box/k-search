<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Uploader",
 *     description="Information about the origin of the publication of data.",
 * )
 */
class Uploader
{
    /**
     * Freely definable name.
     * Can be a single user, a project or a group.
     *
     * @var string
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="John Doe"
     * )
     */
    public $name;

    /**
     * Freely definable organization (since v3.3).
     *
     * @var string
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @JMS\Since("3.3")
     * @SWG\Property(
     *     example="K-Link Inc",
     *     x={"since-version":"3.3"},
     * )
     */
    public $organization;

    /**
     * URL to an human readable website with information about the source entity.
     *
     * @var string
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="https://johndoe-inc.org",
     * )
     */
    public $url;

    /**
     * The URL of the application that triggered the data upload.
     * This data is coming from the Application data in the K-Link Registry.
     *
     * This data is for internal use only, not exposed by default.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @JMS\Groups({"details"})
     */
    public $appUrl;

    /**
     * Contact email to of an administrator, who can be contacted in case of any issues related to uploaded documents.
     * This data is coming from the Application data in the K-Link Registry.
     *
     * This data is for internal use only, not exposed by default.
     *
     * @var string|null
     * @JMS\Type("string")
     * @JMS\Groups({"details"})
     * @JMS\ReadOnly()
     */
    public $email;
}
