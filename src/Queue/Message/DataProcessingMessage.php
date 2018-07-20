<?php

namespace App\Queue\Message;

use App\Entity\DataProcessingStatus;

class DataProcessingMessage extends AbstractDataMessage
{
    public static function fromStatus(DataProcessingStatus $status): self
    {
        return new self($status->getDataUuid(), $status->getRequestId());
    }
}
