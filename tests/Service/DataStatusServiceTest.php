<?php

namespace App\Tests\Service;

use App\Entity\DataProcessingStatus;
use App\Entity\SolrEntityData;
use App\Exception\ProcessingStatusNotFoundException;
use App\Helper\DateHelper;
use App\Model\Data\DataStatus;
use App\Service\DataProcessingService;
use App\Service\DataStatusService;
use App\Service\SolrService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;

class DataStatusServiceTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const REQUEST_ID = 'a1b2c3d4e5f6';

    /**
     * @var DataProcessingService|MockObject
     */
    private $dataProcessingService;

    /**
     * @var SolrService|MockObject
     */
    private $solrService;

    /** @var DataStatusService */
    private $dataStatusService;

    protected function setUp()
    {
        $this->solrService = $this->createMock(SolrService::class);
        $this->dataProcessingService = $this->createMock(DataProcessingService::class);
        $this->dataStatusService = new DataStatusService($this->solrService, $this->dataProcessingService);
    }

    public function isDataNewerDataProvider(): iterable
    {
        yield [false, '2018-05-15T10:10:10.5', '2018-05-15T10:10:10.7'];
        yield [false, '2018-05-15T10:10:10.5', '2018-05-15T10:10:10.5'];
        yield [true, '2018-05-15T10:10:10.5', '2018-05-15T10:10:10.2'];
    }

    /**
     * @dataProvider isDataNewerDataProvider
     */
    public function testIsDataNewer(bool $expected, string $dataUpdatedAt, string $dateComparison): void
    {
        $filterQuery = $this->createMock(FilterQuery::class);

        $this->solrService->expects($this->once())
            ->method('buildFilterQuery')
            ->with(SolrEntityData::FIELD_ENTITY_ID, self::DATA_UUID, 'id')
            ->willReturn($filterQuery);

        $document = new Document([
            SolrEntityData::FIELD_UPDATED_AT => $dataUpdatedAt,
        ]);
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('getNumFound')
            ->willReturn(1);
        $result->expects($this->once())
            ->method('getDocuments')
            ->willReturn([$document]);

        $this->solrService->expects($this->once())
            ->method('getByFilter')
            ->willReturn($result);

        $this->assertSame($expected, $this->dataStatusService->isDataNewer(self::DATA_UUID, DateHelper::createUtcDate($dateComparison)));
    }

    public function testIsDataNewerNoData(): void
    {
        $filterQuery = $this->createMock(FilterQuery::class);

        $this->solrService->expects($this->once())
            ->method('buildFilterQuery')
            ->with(SolrEntityData::FIELD_ENTITY_ID, self::DATA_UUID, 'id')
            ->willReturn($filterQuery);

        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('getNumFound')
            ->willReturn(0);
        $result->expects($this->never())
            ->method('getDocuments');

        $this->solrService->expects($this->once())
            ->method('getByFilter')
            ->willReturn($result);

        $this->assertFalse($this->dataStatusService->isDataNewer(self::DATA_UUID, DateHelper::createUtcDate()));
    }

    public function testGetDataInvalidTypeThrowsException(): void
    {
        $this->dataProcessingService->expects($this->never())
            ->method('getProcessingStatus');
        $this->solrService->expects($this->never())
            ->method('getByFilter');
        $this->solrService->expects($this->never())
            ->method('buildFilterQuery');

        $this->expectException(\InvalidArgumentException::class);
        $this->dataStatusService->getDataStatus('invalid', self::DATA_UUID);
    }

    public function testGetDataStatusFromSolr(): void
    {
        $this->dataProcessingService->expects($this->never())
            ->method('getProcessingStatus');

        /** @var FilterQuery|MockObject $filterQuery */
        $filterQuery = $this->createMock(FilterQuery::class);

        $this->solrService->expects($this->once())
            ->method('buildFilterQuery')
            ->with(SolrEntityData::FIELD_ENTITY_ID, self::DATA_UUID, 'id')
            ->willReturn($filterQuery);

        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('getNumFound')
            ->willReturn(1);

        $document = new Document([
            SolrEntityData::FIELD_UPDATED_AT => DateHelper::formatDate(DateHelper::createUtcDate('2018-05-15T10:10:10.5')),
            SolrEntityData::FIELD_REQUEST_ID => self::REQUEST_ID,
            SolrEntityData::FIELD_STATUS => 'status.code',
            SolrEntityData::FIELD_ERROR_STATUS => 'Status message',
        ]);

        $result->expects($this->once())
            ->method('getDocuments')
            ->willReturn([$document]);
        $this->solrService->expects($this->once())
            ->method('getByFilter')
            ->willReturn($result);

        $status = $this->dataStatusService->getDataStatus(DataStatus::TYPE_DATA, self::DATA_UUID);

        $this->assertSame(self::REQUEST_ID, $status->requestId);
        $this->assertSame('status.code', $status->status);
        $this->assertSame('Status message', $status->message);
        $this->assertSame('2018-05-15T10:10:10Z', DateHelper::formatDate($status->requestReceivedAt));
    }

    public function testGetDataStatusFromProcessing(): void
    {
        $this->solrService->expects($this->never())
            ->method('buildFilterQuery');
        $this->solrService->expects($this->never())
            ->method('getByFilter');

        $dataStatusEntity = new DataProcessingStatus();
        $dataStatusEntity->setDataUuid(self::DATA_UUID);
        $dataStatusEntity->setRequestId(self::REQUEST_ID);
        $dataStatusEntity->setMessage('Status message');
        $dataStatusEntity->setStatus('status.code');
        $dataStatusEntity->setAddedAt(DateHelper::createUtcDate('2018-05-15T10:10:10.5'));

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->willReturn($dataStatusEntity);

        $status = $this->dataStatusService->getDataStatus(DataStatus::TYPE_PROCESSING, self::DATA_UUID);

        $this->assertSame(self::REQUEST_ID, $status->requestId);
        $this->assertSame('status.code', $status->status);
        $this->assertSame('Status message', $status->message);
        $this->assertSame('2018-05-15T10:10:10Z', DateHelper::formatDate($status->requestReceivedAt));
    }

    public function testGetDataStatusFromProcessingNoRecordThrowsException(): void
    {
        $this->solrService->expects($this->never())
            ->method('buildFilterQuery');
        $this->solrService->expects($this->never())
            ->method('getByFilter');

        $this->dataProcessingService->expects($this->once())
            ->method('getProcessingStatus')
            ->willThrowException(new ProcessingStatusNotFoundException());

        $this->expectException(ProcessingStatusNotFoundException::class);
        $this->dataStatusService->getDataStatus(DataStatus::TYPE_PROCESSING, self::DATA_UUID);
    }
}
