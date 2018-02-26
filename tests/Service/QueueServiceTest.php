<?php

namespace App\Tests\Service;

use App\Queue\Message\UUIDMessage;
use App\Service\QueueService;
use Bernard\Queue;
use Bernard\QueueFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueueServiceTest extends TestCase
{
    /**
     * @var QueueFactory|MockObject
     */
    private $queueFactory;

    /**
     * @var Queue|MockObject
     */
    private $queue;

    protected function setUp()
    {
        parent::setUp();
        $this->queueFactory = $this->createMock(QueueFactory::class);
        $this->queue = $this->createMock(Queue::class);
    }

    public function testAddDataMessage()
    {
        $message = new UUIDMessage('123');

        $this->queue->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $queueService = $this->createQueueService(QueueService::DATA_PROCESS_QUEUE);
        $queueService->enqueueMessage(QueueService::DATA_PROCESS_QUEUE, $message);
        $this->assertSame(1, $queueService->countPending(QueueService::DATA_PROCESS_QUEUE));
    }

    private function createQueueService(string $queueName): QueueService
    {
        $this->queueFactory->expects($this->once())
            ->method('create')
            ->with($queueName)
            ->willReturn($this->queue);

        return new QueueService($this->queueFactory);
    }
}
