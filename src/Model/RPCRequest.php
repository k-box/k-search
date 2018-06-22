<?php

namespace App\Model;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * The main class for RPC requests.
 */
class RPCRequest
{
    const REQUEST_ID_HEADER = 'KSearch-Request-Id';

    /**
     * A request identifier established by the client that MUST contain a string or a number.
     *
     * The value SHOULD normally not be empty nor null, and numbers SHOULD NOT contain fractional parts.
     *
     * @var string|null
     * @JMS\Type("string")
     * @SWG\Property(
     *     type="string",
     *     example="request-3d254173",
     * )
     */
    public $id;
}
