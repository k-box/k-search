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
        parent::__construct($responseId);
        $this->error = $error;
    }

    public static function withErrorMessage(int $errorCode, string $errorMessage, string $responseId = null): self
    {
        return new self(new Error($errorCode, $errorMessage), $responseId);
    }
}
