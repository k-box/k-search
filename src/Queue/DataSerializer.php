<?php

namespace App\Queue;

use App\Queue\Message\UUIDMessage;
use Bernard\Envelope;
use Bernard\Serializer;

class DataSerializer implements Serializer
{
    /**
     * @param Envelope $envelope
     *
     * @return string
     */
    public function serialize(Envelope $envelope)
    {
        /** @var UUIDMessage $dataForProcessing */
        $dataForProcessing = $envelope->getMessage();

        return json_encode([
            'uuid' => $dataForProcessing->getUUID(),
            'timestamp' => $envelope->getTimestamp(),
        ]);
    }

    /**
     * @param mixed $serialized
     *
     * @return Envelope
     */
    public function deserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $envelope = new DataEnvelope(new UUIDMessage($data['uuid']));
        $envelope->setTimestamp($data['timestamp']);

        return $envelope;
    }
}
