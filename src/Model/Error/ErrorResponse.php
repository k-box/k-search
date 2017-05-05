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
}
