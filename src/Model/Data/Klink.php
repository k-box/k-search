<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Klink",
 *     description="Object with information on one K-Link.",
 *     required={"id", "name"},
 *     x={"since-version":"3.7"},
 * )
 * @JMS\Since("3.7")
 */
class Klink
{
    /**
     * The K-Link identifier.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd",
     * )
     */
    public $id;

    /**
     * The name of the K-Link.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="Sustainable Land Management"
     * )
     */
    public $name;
}
