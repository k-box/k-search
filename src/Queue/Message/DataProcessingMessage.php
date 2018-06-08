<?php

namespace App\Queue\Message;

use App\Entity\DataProcessingStatus;

class DataProcessingMessage
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $requestId;

    public function __construct(string $uuid, string $requestId)
    {
        $this->uuid = $uuid;
        $this->requestId = $requestId;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public static function fromStatus(DataProcessingStatus $status): self
    {
        return new self($status->getDataUuid(), $status->getRequestId());
    }
}
