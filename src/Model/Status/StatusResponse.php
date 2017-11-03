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
     * @var Status
     *
     * @JMS\Type("App\Model\Status\Status")
     * @SWG\Property(
     *     ref="#/definitions/Status\Status")
     * )
     */
    public $result;

    public function __construct(Status $status, string $responseId = null)
    {
        parent::__construct($responseId);
        $this->result = $status;
    }

    public static function withStatusMessage(int $statusCode, string $message, string $responseId = null): self
    {
        return new self(new Status($statusCode, $message), $responseId);
    }
}
