<?php

namespace App\Model\Data;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\SearchResponse",
 *     required={"result"}
 * )
 */
class SearchResponse extends RPCResponse
{
    /**
     * The response data.
     *
     * @var Data
     *
     * @JMS\Type("App\Model\Data\Data")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $result;

    public function __construct($data, string $responseId = null)
    {
        // @todo include the correct response fields here
        $this->result = $data;
        $this->id = $responseId;
    }
}
