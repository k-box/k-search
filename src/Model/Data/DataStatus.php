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
     * The status code.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     readOnly=true,
     *     enum={"ok", "queued", "error"},
     *     example="queued",
     * )
     */
    public $status;

    /**
     * A message associated with the status with additional details, if relevant.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     readOnly=true,
     *     example={"Status message."}
     * )
     */
    public $message;

    public function __construct(string $status, string $message = '')
    {
        $this->status = $status;
        $this->message = $message;
    }
}
