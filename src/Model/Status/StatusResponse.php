<?php

namespace App\Model\Status;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Status\StatusResponse",
 *     required={"result"}
 * )
 */
class StatusResponse extends RPCResponse
{
    /**
     * The status data.
     *
     * @var Status
     *
     * @JMS\Type("App\Model\Status\Status")
     * @SWG\Property(
     *     readOnly=true,
     *     ref="#/definitions/Status\Status")
     * )
     */
    public $result;

    public function __construct(Status $status, string $responseId = null)
    {
        $this->result = $status;
        $this->id = $responseId;
    }

    public static function withStatusMessage(int $statusCode, string $message, string $responseId = null): StatusResponse
    {
        return new self(new Status($statusCode, $message), $responseId);
    }
}
