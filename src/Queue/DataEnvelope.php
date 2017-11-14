<?php

namespace App\Queue;

use App\Queue\Message\UUIDMessage;
use Bernard\Envelope;

class DataEnvelope extends Envelope
{
    /**
     * @param int $timestamp
     */
    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return UUIDMessage
     */
    public function getMessage(): UUIDMessage
    {
        $message = parent::getMessage();
        if ($message instanceof UUIDMessage) {
            return $message;
        }

        throw new \RuntimeException(sprintf('Wrong data from queue, expecting %s, but got %s', UUIDMessage::class, get_class($message)));
    }
}
