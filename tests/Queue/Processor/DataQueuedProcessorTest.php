<?php

namespace App\Tests\Queue\Processor;

use App\Entity\DataProcessingStatus;
use App\Exception\DataDownloadErrorException;
use App\Exception\InternalSearchException;
use App\Exception\ProcessingStatusNotFoundException;
use App\Exception\SolrExtractionException;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Queue\Processor\DataQueuedProcessor;
use App\Service\DataDownloader;
use App\Service\DataProcessingService;
use App\Service\DataService;
use App\Service\DataStatusService;
use App\Tests\Helper\TestModelHelper;
use Enqueue\Null\NullMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DataQueuedProcessorTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const REQUEST_ID = 'a1b2c3d4e5f6';

    /**
     * @var DataQueuedProcessor
     */
    private $processor;

    /**
     * @var DataService|MockObject
     */
    private $dataService;

    /**
     * @var DataStatusService|MockObject
     */
    private $dataStatusService;

    /**
     * @var DataProcessingService|MockObject
     */
    private $dataProcessingService;

    /**
     * @var DataDownloader|MockObject
     */
    private $dataDownloader;

    protected function setUp()
    {
        $this->dataService = $this->createMock(DataService::class);
        $this->dataStatusService = $this->createMock(DataStatusService::class);
        $this->dataProcessingService = $this->createMock(DataProcessingService::class);
        $this->dataDownloader = $this->createMock(DataDownloader::class);

        $this->processor = new DataQueuedProcessor(
            $this->dataService,
            $this->dataStatusService,
            $this->dataProcessingService,
            $this->dataDownloader,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testDownloadsAndAddsData(): void
    {
        $message = $this->buildMessage();

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'))
            ->setData($data);

        $fileInfo = $this->createMock(\SplFileInfo::class);
        $this->dataDownloader->expects($this->once())
            ->method('getDataFile')
            ->with($data)
            ->willReturn($fileInfo);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $fileInfo)
            ->willReturn(true);

        $this->dataService->expects($this->never())
            ->method('addData');

        $this->dataStatusService->expects($this->once(2))
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $this->anything())
            ->willReturn(false);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testNoTopicRejectsMessage(): void
    {
        $message = $this->buildMessage(null);
        $this->expectsNoDataHandling();

        $this->assertSame(PsrProcessor::REJECT, $this->processor->process($message, $this->buildContext()));
    }

    public function testWrongTopicRejectsMessage(): void
    {
        $message = $this->buildMessage('other-name');
        $this->expectsNoDataHandling();

        $this->assertSame(PsrProcessor::REJECT, $this->processor->process($message, $this->buildContext()));
    }

    public function testProcessingNotInStatusTable(): void
    {
        $message = $this->buildMessage();
        $this->expectsNoDataHandling();

        $this->dataStatusService->expects($this->never())
            ->method('isDataNewer');

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willThrowException(new ProcessingStatusNotFoundException('message'));

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testProcessingDoesNotOverrideNewerData(): void
    {
        $message = $this->buildMessage();
        $this->expectsNoDataHandling();

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'));

        $this->dataStatusService->expects($this->once())
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $status->getAddedAt())
            ->willReturn(true);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testHandlesDownloadErrorAndSavesStatus(): void
    {
        $message = $this->buildMessage();

        $this->dataService->expects($this->never())
            ->method('addDataWithFileExtraction');

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $this->dataDownloader->expects($this->once())
            ->method('getDataFile')
            ->willThrowException(new DataDownloadErrorException('Error downloading!'));

        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'))
            ->setData($data);

        $this->dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $data) {
                $this->assertSame(DataStatus::STATUS_DOWNLOAD_FAIL, $data->status);

                return true;
            }), $this->anything(), $this->anything())
            ->willReturn(true);

        $this->dataStatusService->expects($this->exactly(2))
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $this->anything())
            ->willReturnOnConsecutiveCalls(false, false);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testHandlesDownloadErrorAndDoesNotOverrideNewerData(): void
    {
        $message = $this->buildMessage();

        $this->dataService->expects($this->never())
            ->method('addDataWithFileExtraction');

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $this->dataDownloader->expects($this->once())
            ->method('getDataFile')
            ->willThrowException(new DataDownloadErrorException('Error downloading!'));

        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'))
            ->setData($data);

        $this->dataService->expects($this->never())
            ->method('addData');

        $this->dataStatusService->expects($this->exactly(2))
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $this->anything())
            ->willReturnOnConsecutiveCalls(false, true);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testHandlesSolrErrorAndSavesStatus(): void
    {
        $message = $this->buildMessage();

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'))
            ->setData($data);

        $fileInfo = $this->createMock(\SplFileInfo::class);
        $this->dataDownloader->expects($this->once())
            ->method('getDataFile')
            ->with($data)
            ->willReturn($fileInfo);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $fileInfo)
            ->willThrowException(new InternalSearchException('Solr internal Error'));

        $this->dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $data) {
                $this->assertSame(DataStatus::STATUS_INDEX_FAIL, $data->status);

                return true;
            }), $this->anything(), $this->anything())
            ->willReturn(true);

        $this->dataStatusService->expects($this->exactly(2))
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $this->anything())
            ->willReturnOnConsecutiveCalls(false, false);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testHandlesSolrErrorAndDoesNotOverrideNewerData(): void
    {
        $message = $this->buildMessage();

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'))
            ->setData($data);

        $fileInfo = $this->createMock(\SplFileInfo::class);
        $this->dataDownloader->expects($this->once())
            ->method('getDataFile')
            ->with($data)
            ->willReturn($fileInfo);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $fileInfo)
            ->willThrowException(new InternalSearchException('Solr internal Error'));

        $this->dataService->expects($this->never())
            ->method('addData');

        $this->dataStatusService->expects($this->exactly(2))
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $this->anything())
            ->willReturnOnConsecutiveCalls(false, true);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testHandlesSolrExtractionErrorAndSavesStatus(): void
    {
        $message = $this->buildMessage();

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'))
            ->setData($data);

        $fileInfo = $this->createMock(\SplFileInfo::class);
        $this->dataDownloader->expects($this->once())
            ->method('getDataFile')
            ->with($data)
            ->willReturn($fileInfo);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $fileInfo)
            ->willThrowException(new SolrExtractionException('Solr extraction Error'));

        $this->dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $data) {
                $this->assertSame(DataStatus::STATUS_INDEX_FAIL, $data->status);

                return true;
            }), $this->anything(), $this->anything())
            ->willReturn(true);

        $this->dataStatusService->expects($this->exactly(2))
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $this->anything())
            ->willReturnOnConsecutiveCalls(false, false);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    public function testHandlesSolrExtractionErrorAndDoesNotOverrideNewerData(): void
    {
        $message = $this->buildMessage();

        $this->dataProcessingService->expects($this->once())
            ->method('deleteProcessingStatusByCriteria')
            ->with(self::DATA_UUID, self::REQUEST_ID, DataStatus::STATUS_QUEUED_OK);

        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $status = (new DataProcessingStatus())
            ->setRequestId(self::REQUEST_ID)
            ->setDataUuid(self::DATA_UUID)
            ->setAddedAt(new \DateTime('yesterday'))
            ->setData($data);

        $fileInfo = $this->createMock(\SplFileInfo::class);
        $this->dataDownloader->expects($this->once())
            ->method('getDataFile')
            ->with($data)
            ->willReturn($fileInfo);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $fileInfo)
            ->willThrowException(new SolrExtractionException('Solr extraction Error'));

        $this->dataService->expects($this->never())
            ->method('addData');

        $this->dataStatusService->expects($this->exactly(2))
            ->method('isDataNewer')
            ->with(self::DATA_UUID, $this->anything())
            ->willReturnOnConsecutiveCalls(false, true);

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->with(self::DATA_UUID, self::REQUEST_ID)
            ->willReturn($status);

        $this->assertSame(PsrProcessor::ACK, $this->processor->process($message, $this->buildContext()));
    }

    private function buildMessage($topicName = 'data-queued'): PsrMessage
    {
        $body = json_encode([
            'uuid' => self::DATA_UUID,
            'requestId' => self::REQUEST_ID,
        ]);
        $properties = [];

        if ($topicName) {
            $properties['enqueue.topic_name'] = $topicName;
        }

        return new NullMessage($body, $properties);
    }

    private function expectsNoDataHandling(): void
    {
        $this->dataDownloader->expects($this->never())
            ->method('getDataFile');

        $this->dataService->expects($this->never())
            ->method('addData');

        $this->dataService->expects($this->never())
            ->method('addDataWithFileExtraction');
    }

    private function buildContext(): PsrContext
    {
        return $this->createMock(PsrContext::class);
    }
}
