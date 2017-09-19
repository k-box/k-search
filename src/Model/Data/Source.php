<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Source",
 *     description="An object containing author's information.",
 *     required={""}
 * )
 */
class Source
{
    /**
     * Freely definable source (could be an organization or project).
     *
     * @var string
     * @Assert\Type("string")
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

    /**
     * Information which lets the source contact track back internally the origin of the data. It is suggested to save this information on the client side together with the id of the API request. In easier setups it could also just be the encoded or encrypted “user id” value on the client side. We recommend not to expose personal data here..
     *
     * @var string
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @SWG\Property()
     */
    public $uploadReference;

}
