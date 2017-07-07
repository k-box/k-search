<?php

namespace App\Model\Data;

use App\Model\RPCRequest;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\DeleteRequest",
 *     required={"code", "message"}
 * )
 */
class DeleteRequest extends RPCRequest
{
    /**
      * @var DeleteParams
      * @Assert\Valid()
      * @Assert\NotNull()
      * @JMS\Type("App\Model\Data\DeleteParams")
      * @SWG\Property(
      *     ref="#/definitions/Data\DeleteParams")
      * )
      */
     public $params;
}