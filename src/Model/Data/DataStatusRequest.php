<?php

namespace App\Model\Data;

use App\Model\RPCRequest;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\DataStatusRequest",
 *     required={"params"}
 * )
 */
class DataStatusRequest extends RPCRequest
{
    /**
     * @var UUIDParam
     * @Assert\Valid()
     * @Assert\NotNull()
     * @JMS\Type("App\Model\Data\UUIDParam")
     * @SWG\Property()
     */
    public $params;
}
