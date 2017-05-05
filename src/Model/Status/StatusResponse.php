<?php

namespace App\Model\Status;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Status\StatusResponse",
 *     required={"status"}
 * )
 */
class StatusResponse extends RPCResponse
{
    /**
     * The error data.
     *
     * @var Status
     *
     * @JMS\Type("App\Model\Status\Status")
     * @SWG\Property(
     *     ref="#/definitions/Status\Status")
     * )
     */
    public $status;
}
