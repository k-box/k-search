<?php

namespace App\Tests\Manager;

use App\Manager\DataManager;
use App\Service\QueueService;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\TestCase;

class DataManagerTest extends TestCase
{
    public function testIthandleIndexableDataWithoutTextualContent()
    {
        $data = ModelHelper::createDataModel('123');

        $queueService = $this->createMock(QueueService::class);
        $queueService->expects($this->once())
            ->method('enqueueUUID')
            ->with($data);

        $dataManager = new DataManager($queueService);

        $this->assertTrue($dataManager->saveDataToBeProcessed($data));
    }
}
