<?php

namespace App\Queue\Message;

use App\Entity\DataProcessingStatus;

final class DataProcessingMessage implements \JsonSerializable, MessageInterface
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $requestId;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public static function fromJson(array $data): self
    {
        $s = new self();
        $s->uuid = $data['uuid'] ?? '';
        $s->requestId = $data['requestId'] ?? '';

        return $s;
    }

    public static function fromStatus(DataProcessingStatus $status): self
    {
        $s = new self();
        $s->uuid = $status->getDataUuid();
        $s->requestId = $status->getRequestId();

        return $s;
    }

    public function jsonSerialize()
    {
        return [
            'uuid' => $this->uuid,
            'requestId' => $this->requestId,
        ];
    }
}
