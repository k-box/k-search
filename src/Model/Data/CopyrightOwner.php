<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\CopyrightOwner",
 *     description="The owner of the copyright for the data",
 *     required={"name", "website"}
 * )
 */
class CopyrightOwner
{
    /**
     * Name of the copyright owner.
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="OneOffTech",
     * )
     */
    public $name;

    /**
     * Email of the copyright owner.
     *
     * @var string
     * @JMS\Type("string")
     * @Assert\Email()
     * @SWG\Property(
     *     example="info@oneofftech.xyz",
     * )
     */
    public $email;

    /**
     * Website of the copyright owner.
     *
     * @var string
     * @Assert\Url()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="https://oneofftech.xyz/",
     * )
     */
    public $website;

    /**
     * Address of the copyright owner, if available.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="Warschauer Str. 71 - 10243 Berlin, Germany",
     * )
     */
    public $address;
}
