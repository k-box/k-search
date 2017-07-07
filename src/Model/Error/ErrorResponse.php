<?php

namespace App\Model\Error;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Error\ErrorResponse",
 *     required={"error"},
 * )
 */
class ErrorResponse extends RPCResponse
{
    /**
     * The error data.
     *
     * @var Error
     *
     * @JMS\Type("App\Model\Error\Error")
     * @SWG\Property(
     *     ref="#/definitions/Error\Error")
     * )
     */
    public $error;

    /**
     * ErrorResponse constructor.
     *
     * @param Error       $error
     * @param string|null $id
     */
    public function __construct(Error $error, string $id = null)
    {
        $this->error = $error;
        $this->id = $id;
    }

    /**
     * @param int    $code
     * @param string $message
     * @param string $id
     *
     * @return ErrorResponse
     */
    public static function withErrorMessage(int $code, string $message, string $id)
    {
        return new self(new Error($code, $message), $id);
    }
}
