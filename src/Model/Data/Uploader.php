<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Uploader",
 *     description="Information about the origin of the publication of data.",
 *     required={""}
 * )
 */
class Uploader
{
    /**
     * Freely definable name. Can be a single user, an organization, a project or a group.
     *
     * @var string
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property()
     */
    public $name;

    /**
     * URL to an human readable website with information about the source entity.
     *
     * @var string
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @SWG\Property()
     */
    public $url;

    /**
     * The URL of the application that triggered the data upload.
     *
     * @var string
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $app_url;

    /**
     * Contact email to of an administrator, who can be contacted in case of any issues related to uploaded documents. This data is coming from the Application data in the K-Link Registry.
     *
     * @var string
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $email;
}
