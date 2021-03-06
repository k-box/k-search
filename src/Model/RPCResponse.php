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
     * @var string|null
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     type="string",
     *     example="request3d254173"
     * )
     */
    public $id;

    public function __construct(string $id = null)
    {
        $this->id = $id;
    }
}
