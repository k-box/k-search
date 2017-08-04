<?php

namespace App\Model\Data;

use App\Model\RPCRequest;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\AddRequest",
 *     required={"params"}
 * )
 */
class AddRequest extends RPCRequest
{
    /**
     * @var Data
     * @Assert\Valid()
     * @Assert\NotNull()
     * @JMS\Type("App\Model\Data\AddParams")
     * @SWG\Property(
     *     ref="#/definitions/Data\AddParams")
     * )
     */
    public $params;
}
