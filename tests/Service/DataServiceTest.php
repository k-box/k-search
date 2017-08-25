<?php

namespace App\Tests\Service;

use App\Entity\SolrEntityData;
use App\Service\DataDownloaderService;
use App\Service\DataService;
use App\Service\QueueService;
use App\Service\SolrService;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\TestCase;

class DataServiceTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';

    /** @var SolrService|\PHPUnit_Framework_MockObject_MockObject */
    private $solrService;

    /** @var DataDownloaderService|\PHPUnit_Framework_MockObject_MockObject */
    private $downloadService;

    /** @var QueueService|\PHPUnit_Framework_MockObject_MockObject */
    private $queueService;

    protected function setUp()
    {
        parent::setUp();
        $this->solrService = $this->createMock(SolrService::class);
        $this->downloadService = $this->createMock(DataDownloaderService::class);
        $this->queueService = $this->createMock(QueueService::class);
    }

    public function testItDeletesData()
    {
        $this->solrService->expects($this->exactly(2))
            ->method('delete')
            ->willReturnOnConsecutiveCalls(true, false);

        $dataService = new DataService($this->queueService, $this->solrService, $this->downloadService);

        $this->assertTrue($dataService->deleteData('existing-uuid'));
        $this->assertFalse($dataService->deleteData('uneexisting-uuid'));
    }

    public function testItAddDataWithTextualContent()
    {
        $sampleTextualContent = 'example indeaxable content';
        $data = ModelHelper::createDataModel(self::DATA_UUID);

        $this->solrService->expects($this->once())
            ->method('add')
            ->with($this->anything(), $sampleTextualContent)
            ->willReturn(true);

        $this->queueService->expects($this->never())
            ->method('enqueueUUID');

        $dataService = new DataService($this->queueService, $this->solrService, $this->downloadService);
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
    }

    public function testItAddsDataNotIndexable()
    {
        $sampleTextualContent = '';
        $data = ModelHelper::createDataModel(self::DATA_UUID);
        $data->type = 'non-indexable-type';

        $this->solrService->expects($this->once())
            ->method('add')
            ->with($this->anything(), $sampleTextualContent)
            ->willReturn(true);

        $this->queueService->expects($this->never())
            ->method('enqueueUUID');

        $dataService = new DataService($this->queueService, $this->solrService, $this->downloadService);
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
    }

    public function testItAddsDataWithoutTextualContentAndItQueuesItForDownloading()
    {
        $sampleTextualContent = '';
        $data = ModelHelper::createDataModel(self::DATA_UUID);

        $this->solrService->expects($this->once())
            ->method('add')
            ->with($this->callback(function (SolrEntityData $data) {
                $this->assertEquals(SolrEntityData::DATA_STATUS_QUEUED, $data->getField('str_ss_data_status'));

                return true;
            }), '');

        $this->queueService->expects($this->once())
            ->method('enqueueUUID');

        $dataService = new DataService($this->queueService, $this->solrService, $this->downloadService);
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
    }

    public function testItProccessDataFromQueue()
    {
        $sampleContent = 'fake content';
        $data = ModelHelper::createDataModel(self::DATA_UUID);

        $this->queueService->expects($this->once())
            ->method('dequeueUUID')
            ->willReturn(self::DATA_UUID);

        $this->solrService->expects($this->once())
            ->method('add')
            ->with($this->callback(function (SolrEntityData $data) {
                $this->assertEquals(SolrEntityData::DATA_STATUS_OK, $data->getField('str_ss_data_status'));

                return true;
            }), $sampleContent);

        $this->downloadService->expects($this->once())
            ->method('getFileContents')
            ->with($data)
            ->willReturn($sampleContent);

        $dataService = $this->getMockBuilder(DataService::class)
            ->setMethods(['getData'])
            ->setConstructorArgs([$this->queueService, $this->solrService, $this->downloadService])
            ->getMock();

        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $dataService->processDataFromQueue();
    }

    public function testItHandlesWhenThereIsNoMoreItemsInQueue()
    {
        $this->queueService->expects($this->once())
            ->method('dequeueUUID')
            ->willReturn(null);

        $this->solrService->expects($this->never())
            ->method('add');

        $this->downloadService->expects($this->never())
            ->method('getFileContents');

        $dataService = new DataService($this->queueService, $this->solrService, $this->downloadService);

        $this->assertFalse($dataService->processDataFromQueue());
    }
}
