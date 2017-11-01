<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Uploader",
 *     description="Information about the origin of the publication of data.",
 *     required={"name"}
 * )
 */
class Uploader
{
    /**
     * Freely definable name.
     * Can be a single user, an organization, a project or a group.
     *
     * @var string
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="John Doe Inc."
     * )
     */
    public $name;

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
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @JMS\Groups({"details"})
     * @SWG\Property(
     *     property="app_url",
     *     example="https://wwww.johndoe-inc.org/uploader-app",
     * )
     */
    public $appUrl;

    /**
     * Contact email to of an administrator, who can be contacted in case of any issues related to uploaded documents.
     * This data is coming from the Application data in the K-Link Registry.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({"details"})
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="admin@johndoe-inc.org",
     * )
     */
    public $email;
}
