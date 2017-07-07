<?php

namespace App\Model;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The main class for RPC requests.
 */
class RPCRequest
{
    /**
     * A request identifier established by the client that MUST contain a string or a number.
     *
     * The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts.
     *
     * @var string
     *
     * @JMS\Type("string")
     * @Assert\NotBlank()
     * @SWG\Property(
     *     example="request-3d254173"
     * )
     */
    public $id;
}
