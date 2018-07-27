<?php

namespace App\Queue\Message;

abstract class AbstractDataMessage
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $requestId;

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
}
