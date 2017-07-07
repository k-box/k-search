<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\DeleteParams",
 *     required={"uuid"}
 * )
 */
class DeleteParams
{
    /**
     * The universally unique identifier of the data piece to be deleted.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd"
     * )
     */
    public $uuid;
}
