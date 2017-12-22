<?php

namespace App\Model\Data\Search;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\Search\SearchResponse",
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
     * @JMS\Type("App\Model\Data\Search\SearchResults")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $result;

    public function __construct($result, string $responseId = null)
    {
        parent::__construct($responseId);
        $this->result = $result;
    }
}
