<?php

namespace App\Tests\Service;

use App\Service\QueueService;
use App\Tests\Helper\ModelHelper;

class QueueServiceTest extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;
    /**
     * @var QueueService
     */
    private $queueService;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();

        $this->queueService = $this->container->get(QueueService::class);
        $this->queueService->reset();
    }

    public function testItQueuesAMessage()
    {
        $this->queueService->enqueueUUID(ModelHelper::createDataModel('123'));
        $this->assertEquals(1, $this->queueService->countPending());
        $this->queueService->enqueueUUID(ModelHelper::createDataModel('456'));
        $this->assertEquals(2, $this->queueService->countPending());
        $this->queueService->enqueueUUID(ModelHelper::createDataModel('789'));
        $this->assertEquals(3, $this->queueService->countPending());
    }

    public function testItDequeueAMessageInFIFO()
    {
        $data1 = ModelHelper::createDataModel('123');
        $data2 = ModelHelper::createDataModel('456');
        $data3 = ModelHelper::createDataModel('789');

        $this->queueService->enqueueUUID($data1);
        $this->queueService->enqueueUUID($data2);
        $this->queueService->enqueueUUID($data3);

        $this->assertEquals(3, $this->queueService->countPending());

        $retrievedData1 = $this->queueService->dequeueUUID();
        $this->assertEquals(2, $this->queueService->countPending());
        $retrievedData2 = $this->queueService->dequeueUUID();
        $this->assertEquals(1, $this->queueService->countPending());
        $retrievedData3 = $this->queueService->dequeueUUID();
        $this->assertEquals(0, $this->queueService->countPending());

        $this->assertEquals('123', $retrievedData1);
        $this->assertEquals('456', $retrievedData2);
        $this->assertEquals('789', $retrievedData3);
    }

    public function testItReturnsNullWhenNoMessage()
    {
        $this->assertEquals(0, $this->queueService->countPending());
        $this->assertNull($this->queueService->dequeueUUID());
    }
}
