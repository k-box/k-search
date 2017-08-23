<?php

namespace App\Tests\Service;

use App\Entity\SolrEntityData;
use App\Helper\DataHelper;
use App\Manager\DataManager;
use App\Service\DataDownloaderService;
use App\Service\DataService;
use App\Service\QueueService;
use App\Service\SolrService;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\TestCase;

class DataServiceTest extends TestCase
{
    public function testItDeletesData()
    {
        $solrServiceMock = $this->getMockBuilder(SolrService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $solrServiceMock->expects($this->exactly(2))
            ->method('delete')
            ->willReturnOnConsecutiveCalls(true, false);

        $dataHelper = $this->createMock(DataHelper::class);
        $queueService = $this->createMock(QueueService::class);
        $downloadService = $this->createMock(DataDownloaderService::class);


        $dataService = new DataService($queueService, $solrServiceMock, $dataHelper, $downloadService);

        $this->assertTrue($dataService->deleteData('existing-uuid'));
        $this->assertFalse($dataService->deleteData('uneexisting-uuid'));
    }

    public function testItAddDataWithTextualContent()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleTextualContent = 'example indeaxable content';
        $data = ModelHelper::createDataModel($sampleUUID);

        $solrServiceMock = $this->getMockBuilder(SolrService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $solrServiceMock->expects($this->once())
            ->method('add')
            ->with($this->anything(), $sampleTextualContent)
            ->willReturn(true);

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->never())
            ->method('isIndexable');

        $queueService = $this->createMock(QueueService::class);
        $queueService->expects($this->never())
            ->method('enqueueUUID');

        $downloadService = $this->createMock(DataDownloaderService::class);

        $dataService = new DataService($queueService, $solrServiceMock, $dataHelper, $downloadService);
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
    }

    public function testItAddsDataNotIndexable()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleTextualContent = '';
        $data = ModelHelper::createDataModel($sampleUUID);

        $solrServiceMock = $this->getMockBuilder(SolrService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $solrServiceMock->expects($this->once())
            ->method('add')
            ->with($this->anything(), $sampleTextualContent)
            ->willReturn(true);

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('isIndexable')
            ->willReturn(false);

        $queueService = $this->createMock(QueueService::class);
        $queueService->expects($this->never())
            ->method('enqueueUUID');

        $downloadService = $this->createMock(DataDownloaderService::class);

        $dataService = new DataService($queueService, $solrServiceMock, $dataHelper, $downloadService);
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
    }

    public function testItAddsDataWithoutTextualContentAndItQueuesItForDownloading()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleTextualContent = '';
        $data = ModelHelper::createDataModel($sampleUUID);

        $solrServiceMock = $this->getMockBuilder(SolrService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $solrServiceMock->expects($this->once())
            ->method('add');

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('isIndexable')
            ->willReturn(true);

        $queueService = $this->createMock(QueueService::class);
        $queueService->expects($this->once())
            ->method('enqueueUUID');

        $downloadService = $this->createMock(DataDownloaderService::class);

        $dataService = new DataService($queueService, $solrServiceMock, $dataHelper, $downloadService);
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
    }

    public function testItProccessDataFromQueue()
    {
        $sampleContent = 'fake content';
        $sampleUUID = '123';
        $data = ModelHelper::createDataModel($sampleUUID);

        $queueService = $this->createMock(QueueService::class);
        $queueService->expects($this->once())
            ->method('dequeueUUID')
            ->willReturn('123');

        $solrService = $this->createMock(SolrService::class);
        $solrService->expects($this->once())
            ->method('add')
            ->with($this->anything(), $sampleContent);

        $dataHelper = $this->createMock(DataHelper::class);

        $downloadService = $this->createMock(DataDownloaderService::class);
        $downloadService->expects($this->once())
            ->method('getFileContents')
            ->with($data)
            ->willReturn($sampleContent);

        $dataService = $this->getMockBuilder(DataService::class)
            ->setMethods(['getData'])
            ->setConstructorArgs([$queueService,$solrService,$dataHelper,$downloadService])
            ->getMock();

        $dataService->expects($this->once())
            ->method('getData')
            ->with($sampleUUID)
            ->willReturn($data);

        $dataService->processDataFromQueue();
    }
}
