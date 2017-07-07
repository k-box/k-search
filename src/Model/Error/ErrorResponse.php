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

    public function __construct(Error $error, string $responseId = null)
    {
        $this->error = $error;
        $this->id = $responseId;
    }

    public static function withErrorMessage(int $errorCode, string $errorMessage, string $responseId = null): ErrorResponse
    {
        return new self(new Error($errorCode, $errorMessage), $responseId);
    }
}
