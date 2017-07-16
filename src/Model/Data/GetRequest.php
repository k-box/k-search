<?php

namespace App\Model\Data;

use App\Model\RPCRequest;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\GetRequest",
 *     required={"params"}
 * )
 */
class GetRequest extends RPCRequest
{
    /**
      * @var UUIDParam
      * @Assert\Valid()
      * @Assert\NotNull()
      * @JMS\Type("App\Model\Data\UUIDParam")
      * @SWG\Property(
      *     ref="#/definitions/Data\UUIDParam")
      * )
      */
     public $params;
}
