<?php

namespace App\Queue;

use App\Queue\Message\UUIDForProcessing;
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
     * @return UUIDForProcessing
     */
    public function getMessage(): UUIDForProcessing
    {
        return parent::getMessage();
    }
}
