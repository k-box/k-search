<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Author",
 *     description="An object containing author's information.",
 *     required={"name"}
 * )
 */
class Author
{
    /**
     * Name of the author.
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="Arthur Conan Doyle",
     * )
     */
    public $name;

    /**
     * Contact email of author.
     *
     * @var string
     * @Assert\Email
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="arthur@conan.doyle",
     * )
     */
    public $email;

    /**
     * General contact information (e.g. URL to website or postal address).
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="221B Baker Street",
     * )
     */
    public $contact;
}
