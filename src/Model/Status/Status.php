<?php

namespace App\Model\Status;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Status\Status",
 *     required={"code", "status"}
 * )
 */
class Status
{
    /**
     * JSON-RPC inspired error codes.
     *
     * @var int
     * @JMS\Type("integer")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example=201
     * )
     */
    public $code;

    /**
     * The status message.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     example="Ok"
     * )
     */
    public $status;

    /**
     * Status constructor.
     *
     * @param int    $code
     * @param string $status
     */
    public function __construct(int $code, string $status)
    {
        $this->code = $code;
        $this->status = $status;
    }
}
