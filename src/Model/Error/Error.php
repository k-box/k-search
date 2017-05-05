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

    /**
     * Error constructor.
     *
     * @param int    $code
     * @param string $message
     */
    public function __construct(int $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }
}
