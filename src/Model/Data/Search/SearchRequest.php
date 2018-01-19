<?php

namespace App\Model\Data\Search;

use App\Model\RPCRequest;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Search\SearchRequest",
 *     required={"params"}
 * )
 */
class SearchRequest extends RPCRequest
{
    /**
     * @var SearchParams
     * @Assert\Valid()
     * @Assert\NotNull()
     * @JMS\Type("App\Model\Data\Search\SearchParams")
     * @SWG\Property()
     */
    public $params;
}
