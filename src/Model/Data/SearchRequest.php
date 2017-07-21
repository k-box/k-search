<?php

namespace App\Model\Data;

use App\Model\RPCRequest;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\SearchRequest",
 *     required={"params"}
 * )
 */
class SearchRequest extends RPCRequest
{
    /**
     * @var SearchParams
     * @Assert\Valid()
     * @Assert\NotNull()
     * @JMS\Type("App\Model\Data\SearchParams")
     * @SWG\Property()
     */
    public $params;
}
