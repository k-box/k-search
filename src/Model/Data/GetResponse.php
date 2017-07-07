<?php

namespace App\Model\Data;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\GetResponse",
 *     required={"result"}
 * )
 */
class GetResponse extends RPCResponse
{
    /**
     * The response data.
     *
     * @var Data
     *
     * @JMS\Type("App\Model\Data\Data")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     ref="#/definitions/Data\Data")
     * )
     */
    public $result;

    public function __construct(Data $data, string $responseId = null)
    {
        $this->result = $data;
        $this->id = $responseId;
    }
}
