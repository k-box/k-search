<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Data",
 *     required={"uuid", "hash", "type", "url"}
 * )
 */
class Data
{
    /**
     * The Universally unique identifier of this data.
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Uuid()
     * @SWG\Property()
     */
    public $uuid;

    /**
     * The URI where the source data is stored and retrievable.
     *
     * @var string
     * @Assert\NotBlank()
     * @SWG\Property()
     */
    public $url;

    /**
     * The SHA-2 hash of the Document contents (SHA-512, thus 128 Chars).
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(128)
     * @JMS\Type("string")
     * @SWG\Property()
     */
    public $hash;

    /**
     * The general type of the provided data.
     *
     * @var string
     * @Assert\NotNull()
     * @JMS\Type("string")
     * @Assert\Choice(
     *     strict=true,
     *     choices={"document", "video"}
     * )
     * @SWG\Property(
     *     enum={"document", "video"}
     * )
     */
    public $type;
}
