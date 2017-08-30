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
     * @var SearchResults
     *
     * @JMS\Type("App\Model\Data\SearchResults")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $result;

    public function __construct($result, string $responseId = null)
    {
        $this->result = $result;
        $this->id = $responseId;
    }
}
