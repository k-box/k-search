<?php

namespace App\Model\Error;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Error\Error",
 *     required={"code", "message"}
 * )
 */
class Error
{
    /**
     * The error constants are a representation of the RPC standard error codes. More info in http://json-rpc.readthedocs.io/en/latest/exceptions.html
     */
    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;
    const INTERNAL_ERROR = -32603;

    /**
     * JSON-RPC inspired error codes.
     *
     * @var int
     * @JMS\Type("integer")
     * @SWG\Property(
     *     example=404
     * )
     */
    public $code;

    /**
     * Human readable error message.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="Not Found"
     * )
     */
    public $message;

    /**
     * Additional information can optionally be provided on errors for better debugging.
     *
     * @todo: properly define the structure of this property.
     */
    public $data;

    public function __construct(int $errorCode, string $message, $data = null)
    {
        $this->code = $errorCode;
        $this->message = $message;
        $this->data = $data;
    }
}
