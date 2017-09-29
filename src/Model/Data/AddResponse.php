<?php

namespace App\Model\Data;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\AddResponse",
 *     required={"result"}
 * )
 */
class AddResponse extends RPCResponse
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
        parent::__construct($responseId);
        $this->result = $data;
    }
}
