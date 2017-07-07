<?php

namespace App\Model;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * The base class for RPC responses.
 */
class RPCResponse
{
    /**
     * The request ID this response is referring to.
     *
     * @see RPCRequest::$id
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="request-3d254173"
     * )
     */
    public $id;
}
