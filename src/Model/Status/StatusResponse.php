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
     * @var DataStatus
     *
     * @JMS\Type("App\Model\Status\Status")
     * @SWG\Property(
     *     readOnly=true,
     *     ref="#/definitions/Status\Status")
     * )
     */
    public $result;

    public function __construct(DataStatus $status, string $responseId = null)
    {
        parent::__construct($responseId);
        $this->result = $status;
    }

    public static function withStatusMessage(int $statusCode, string $message, string $responseId = null): StatusResponse
    {
        return new self(new DataStatus($statusCode, $message), $responseId);
    }
}
