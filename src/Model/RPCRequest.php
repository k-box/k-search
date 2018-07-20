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
    const REQUEST_ID_HEADER = 'KSearch-Request-Id';

    /**
     * A request identifier established by the client, must not be empty.
     *
     * @var string|null
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     type="string",
     *     example="request-3d254173",
     * )
     */
    public $id;
}
