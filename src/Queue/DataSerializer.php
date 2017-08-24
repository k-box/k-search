<?php

namespace App\Queue;

use App\Queue\Message\UUIDForProcessing;
use Bernard\Envelope;

class DataSerializer implements \Bernard\Serializer
{
    /**
     * @param Envelope $envelope
     *
     * @return string
     */
    public function serialize(Envelope $envelope)
    {
        /** @var UUIDForProcessing $dataForProcessing */
        $dataForProcessing = $envelope->getMessage();

        return json_encode([
            'uuid' => $dataForProcessing->getDataUUID(),
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
        $envelope = new DataEnvelope(new UUIDForProcessing($data['uuid']));
        $envelope->setTimestamp($data['timestamp']);

        return $envelope;
    }
}
