<?php

namespace App\Service;

use App\Model\Data\Data;
use App\Queue\DataEnvelope;
use App\Queue\DataSerializer;
use App\Queue\Message\UUIDForProcessing;
use Bernard\Driver;
use Bernard\Envelope;
use Bernard\QueueFactory\PersistentFactory;

class QueueService
{
    const QUEUE_NAME = 'data-to-process';

    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var PersistentFactory
     */
    private $factory;

    public function __construct(\App\Queue\QueueFactory $factory)
    {
        $this->factory = $factory;
        $this->queue = $this->factory->create(self::QUEUE_NAME);
    }

    public function enqueueUUID(Data $data): void
    {
        $message = new UUIDForProcessing($data->uuid);
        $this->queue->enqueue(new Envelope($message));
    }

    public function dequeueUUID()
    {
        /** @var DataEnvelope $envelope */
        $envelope = $this->queue->dequeue();

        return $envelope->getMessage()->getDataUUID();
    }

    public function countPending(): int
    {
        return $this->queue->count();
    }

    public function reset(): void
    {
        $this->factory->remove(self::QUEUE_NAME);
        $this->queue = $this->factory->create(self::QUEUE_NAME);
    }
}
