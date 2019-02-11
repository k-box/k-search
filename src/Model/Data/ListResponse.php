<?php

namespace App\Model\Data;

use App\Model\RPCResponse;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\ListResponse",
 *     required={"result"}
 * )
 */
class ListResponse extends RPCResponse
{
    /**
     * The response data.
     *
     * @var Klink[]
     *
     * @JMS\Type("array<App\Model\Data\Klink>")
     * @JMS\ReadOnly()
     * @SWG\Property()
     */
    public $result;

    public function __construct(array $data, string $responseId = null)
    {
        parent::__construct($responseId);
        $this->result = $data;
    }
}
