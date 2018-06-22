<?php

namespace App\Model\Data;

use App\Entity\DataProcessingStatus;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\DataStatus",
 *     required={"status", "request_id"}
 * )
 */
class DataStatus
{
    public const TYPE_DATA = 'data';
    public const TYPE_PROCESSING = 'processing';

    public const STATUS_QUEUED_OK = 'queued.ok';
    public const STATUS_DOWNLOAD_FAIL = 'download.fail';
    public const STATUS_INDEX_OK = 'index.ok';
    public const STATUS_INDEX_FAIL = 'index.fail';

    /**
     * The status code.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     readOnly=true,
     *     enum={"index.ok", "index.fail", "queued.ok", "download.fail"},
     *     example="index.ok",
     * )
     */
    public $status;

    /**
     * A message associated with the status with additional details, if relevant.
     *
     * @var string|null
     * @JMS\Type("string")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     readOnly=true,
     *     example="Status message.",
     * )
     */
    public $message;

    /**
     * The status type, used to get the status from different stages.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\Since("3.4")
     * @SWG\Property(
     *     readOnly=true,
     *     enum={"data","processing"},
     *     x={"since-version":"3.4"},
     * )
     */
    public $type;

    /**
     * The request that originated the data to be in this state.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\Since("3.4")
     * @JMS\ReadOnly()
     * @SWG\Property(
     *     property="request_id",
     *     readOnly=true,
     *     example="request-3d254173",
     *     x={"since-version":"3.4"},
     * )
     */
    public $requestId;

    /**
     * The time the originated request was made.
     *
     * @var \DateTimeInterface
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s\Z', 'UTC'>")
     * @JMS\ReadOnly()
     * @JMS\Since("3.4")
     * @SWG\Property(
     *     property="request_received_at",
     *     readOnly=true,
     *     example="2018-04-22T14:47:31Z",
     *     x={"since-version":"3.4"},
     * )
     */
    public $requestReceivedAt;

    public static function fromData(Data $data): self
    {
        $s = new self();
        $s->status = $data->status;
        $s->message = $data->errorStatus;
        $s->requestId = $data->requestId;
        $s->requestReceivedAt = $data->updatedAt;

        return $s;
    }

    public static function fromProcessingStatus(DataProcessingStatus $status): self
    {
        $s = new self();
        $s->status = $status->getStatus();
        $s->requestId = $status->getRequestId();
        $s->requestReceivedAt = $status->getAddedAt();
        $s->message = $status->getMessage();

        return $s;
    }
}
