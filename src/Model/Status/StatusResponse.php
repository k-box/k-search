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
     * The status data.
     *
     * @var Status
     *
     * @JMS\Type("App\Model\Status\Status")
     * @SWG\Property(
     *     ref="#/definitions/Status\Status")
     * )
     */
    public $result;

    /**
     * ErrorResponse constructor.
     *
     * @param Status      $status
     * @param string|null $id
     */
    public function __construct(Status $status, string $id = null)
    {
        $this->result = $status;
        $this->id = $id;
    }

    /**
     * @param int    $code
     * @param string $message
     * @param string $id
     *
     * @return StatusResponse
     */
    public static function withStatusMessage(int $code, string $message, string $id)
    {
        return new self(new Status($code, $message), $id);
    }
}
