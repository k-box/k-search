<?php

namespace App\Service;

use Bernard\Envelope;
use Bernard\Message;
use Bernard\Queue;
use Bernard\QueueFactory;

class QueueService
{
    public const DATA_PROCESS_QUEUE = 'data-process-queue';

    private const QUEUES = [
        self::DATA_PROCESS_QUEUE,
    ];

    /**
     * @var QueueFactory
     */
    private $factory;

    /**
     * @var Queue[]
     */
    private $queues = [];

    public function __construct(QueueFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Queue into the given queue the provided message. The queue is created if does not exist.
     *
     * @param string  $queueName
     * @param Message $message
     */
    public function enqueueMessage(string $queueName, Message $message)
    {
        $this->ensureQueueExists($queueName);
        $this->queues[$queueName]->enqueue(new Envelope($message));
    }

    /**
     * Returns a message from the given queue.
     *
     * @param string $queueName
     *
     * @return Message|null
     */
    public function dequeMessage(string $queueName): ?Message
    {
        $envelope = $this->dequeEnvelope($queueName);
        if (!$envelope) {
            return null;
        }

        return $envelope->getMessage();
    }

    public function dequeEnvelope(string $queueName): ?Envelope
    {
        $this->ensureQueueExists($queueName);

        return $this->queues[$queueName]->dequeue();
    }

    public function countPending(string $queueName): int
    {
        $this->ensureQueueExists($queueName);

        return $this->queues[$queueName]->count();
    }

    public function reset(string $queueName): void
    {
        $this->ensureQueueIsValid($queueName);

        $this->factory->remove($queueName);
        $this->ensureQueueExists($queueName);
    }

    private function ensureQueueIsValid(string $queueName)
    {
        if (!\in_array($queueName, self::QUEUES, true)) {
            throw new \RuntimeException(sprintf('Queue %s does not exist!', $queueName));
        }
    }

    private function ensureQueueExists(string $queueName)
    {
        $this->ensureQueueIsValid($queueName);

        if (!array_key_exists($queueName, $this->queues)) {
            $this->queues[$queueName] = $this->factory->create($queueName);
        }
    }
}
