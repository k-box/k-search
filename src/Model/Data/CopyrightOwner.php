<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\CopyrightOwner",
 *     description="The copyright owner and information on how to contact for any inquiries",
 *     required={"contact"}
 * )
 */
class CopyrightOwner
{
    /**
     * Name of the copyright owner.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="KLink Organization",
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
     *     example="info@klink.asia",
     * )
     */
    public $email;

    /**
     * General contact information (e.g. URL to the owner website or postal address).
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="KLink Website: http://www.klink.asia",
     * )
     */
    public $contact;
}
