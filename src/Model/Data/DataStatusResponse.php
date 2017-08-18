<?php

namespace App\Model\Data;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\StatusResponse",
 *     required={"result"}
 * )
 */
class DataStatusResponse extends RPCResponse
{
    /**
     * The data Status.
     *
     * @var DataStatus
     *
     * @JMS\Type("App\Model\Data\DataStatus")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $result;

    public function __construct(DataStatus $status, string $responseId = null)
    {
        parent::__construct($responseId);
        $this->result = $status;
    }
}
