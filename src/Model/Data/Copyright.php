<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Copyright",
 *     description="An object containing information on the copyright",
 *     required={"owner", "usage",}
 * )
 */
class Copyright
{
    /**
     * The copyright owner and information on how to contact for any inquiries.
     *
     * @var CopyrightOwner
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @JMS\Type("App/Model/Data/CopyrightOwner")
     * @SWG\Property()
     */
    public $owner;

    /**
     * The conditions of use of the copyrighted data.
     *
     * @var CopyrightUsage
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @JMS\Type("App/Model/Data/CopyrightUsage")
     * @SWG\Property()
     */
    public $usage;
}
