<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\DataStatus",
 *     required={"status"}
 * )
 */
class DataStatus
{
    /**
     * The status.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="Queued",
     * )
     */
    public $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }
}
