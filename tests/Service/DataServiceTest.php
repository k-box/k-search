<?php

namespace App\Tests\Service;

use App\Entity\SolrEntityData;
use App\Exception\BadRequestException;
use App\Model\Data\Data;
use App\Queue\Message\UUIDMessage;
use App\Service\DataDownloaderService;
use App\Service\DataService;
use App\Service\QueueService;
use App\Service\SolrService;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;

class DataServiceTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';

    /** @var SolrService|\PHPUnit_Framework_MockObject_MockObject */
    private $solrService;

    /** @var QueueService|\PHPUnit_Framework_MockObject_MockObject */
    private $queueService;

    /** @var DataService */
    private $dataService;

    /** @var DataDownloaderService|\PHPUnit_Framework_MockObject_MockObject */
    private $downloaderService;

    protected function setUp()
    {
        parent::setUp();
        $this->solrService = $this->createMock(SolrService::class);
        $this->queueService = $this->createMock(QueueService::class);
        $this->downloaderService = $this->createMock(DataDownloaderService::class);
        $types = [
            'application/pdf',
            'image/jpg',
            'text/html',
        ];

        $this->dataService = new DataService(
            $this->queueService,
            $this->solrService,
            $this->downloaderService,
            $this->createMock(LoggerInterface::class),
            $types
        );
    }

    public function provideIndexableContentTypes(): array
    {
        return [
            ['image/jpg'],
            ['text/html; charset=iso-8859-15'],
        ];
    }

    /**
     * @dataProvider provideIndexableContentTypes
     *
     * @param string $contentType
     */
    public function testHasIndexableContentType(string $contentType)
    {
        $data = new Data();
        $data->url = 'http://someurls.com/data.ext';

        $this->downloaderService->expects($this->once())
            ->method('getDataUrlHeaders')
            ->with($this->callback(function (Data $data) {
                $this->assertSame($data->url, 'http://someurls.com/data.ext');

                return true;
            }))
            ->willReturn([
                'Content-Type' => [$contentType],
            ]);

        $this->dataService->ensureDataIsIndexable($data);
    }

    public function testHasNotIndexableContentType()
    {
        $data = new Data();
        $data->url = 'http://someurls.com/data.ext';

        $this->downloaderService->expects($this->once())
            ->method('getDataUrlHeaders')
            ->willReturn([
                'Content-Type' => ['image/jpeg2000'],
            ]);

        $this->expectException(BadRequestException::class);
        $this->dataService->ensureDataIsIndexable($data);
    }

    public function testHasNoContentTypeHeaders()
    {
        $data = new Data();
        $data->url = 'http://someurls.com/data.ext';

        $this->downloaderService->expects($this->once())
            ->method('getDataUrlHeaders')
            ->willReturn([]);
        $this->expectException(BadRequestException::class);

        $this->dataService->ensureDataIsIndexable($data);
    }

    public function dataProviderForDeleteData()
    {
        return [
            'existing' => [true, true],
            'not-existing' => [false, false],
        ];
    }

    /**
     * @dataProvider dataProviderForDeleteData
     *
     * @param bool $expected
     * @param bool $existing
     */
    public function testItDeletesData(bool $expected, bool $existing)
    {
        $this->solrService->expects($this->exactly(1))
            ->method('delete')
            ->with(SolrEntityData::getEntityType(), self::DATA_UUID)
            ->willReturn($existing);

        $this->assertEquals($expected, $this->dataService->deleteData(self::DATA_UUID));
    }

    public function testItAddDataWithTextualContent()
    {
        $sampleTextualContent = 'example indeaxable content';
        $data = ModelHelper::createDataModel(self::DATA_UUID);

        $this->solrService->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(function (SolrEntityData $data) {
                    $this->assertEquals(Data::STATUS_OK, $data->getField(SolrEntityData::FIELD_STATUS));

                    return true;
                }))
            ->willReturn(true);

        $this->queueService->expects($this->never())
            ->method('enqueueMessage');

        $this->assertTrue($this->dataService->addData($data, $sampleTextualContent));
    }

    public function dataProviderForNotIndexableContentAndType()
    {
        return [
            ['', 'non-indexable-type'],
            [null, 'non-indexable-type'],
        ];
    }

    /**
     * @dataProvider dataProviderForNotIndexableContentAndType
     *
     * @param        $textContents
     * @param string $type
     */
    public function testThrowsExceptionIfDataIsNotIndexable($textContents, string $type)
    {
        $data = ModelHelper::createDataModel(self::DATA_UUID);
        $data->type = $type;

        $this->queueService->expects($this->never())
            ->method('enqueueMessage');

        $this->expectException(BadRequestException::class);
        $this->dataService->addData($data, $textContents);
    }

    public function testItQueuesIndexableData()
    {
        $data = ModelHelper::createDataModel(self::DATA_UUID);

        $this->solrService->expects($this->once())
            ->method('add')
            ->with(
            $this->callback(function (SolrEntityData $data) {
                $this->assertEquals(Data::STATUS_QUEUED, $data->getField(SolrEntityData::FIELD_STATUS));

                return true;
            }))
            ->willReturn(true);

        $this->queueService->expects($this->once())
            ->method('enqueueMessage')
            ->with(QueueService::DATA_PROCESS_QUEUE, $this->callback(function (UUIDMessage $message) {
                $this->assertSame(self::DATA_UUID, $message->getUUID());

                return true;
            }))
        ;

        $this->downloaderService->expects($this->once())
            ->method('getDataUrlHeaders')
            ->willReturn([
                'Content-Type' => ['image/jpg'],
            ]);

        $this->assertTrue($this->dataService->addData($data));
    }

    public function testAddDataWithFileExtraction()
    {
        $data = ModelHelper::createDataModel(self::DATA_UUID);
        /** @var SplFileInfo|\PHPUnit_Framework_MockObject_MockObject $file */
        $file = $this->createMock(SplFileInfo::class);

        $this->solrService->expects($this->once())
            ->method('addWithTextExtraction')
            ->with(
                $this->callback(function (SolrEntityData $data) {
                    $this->assertEquals(Data::STATUS_OK, $data->getField(SolrEntityData::FIELD_STATUS));

                    return true;
                }),
                $this->callback(function (\SplFileInfo $file) {
                    return true;
                }))
            ->willReturn(true);

        $this->queueService->expects($this->never())
            ->method('enqueueMessage')
        ;

        $this->assertTrue($this->dataService->addDataWithFileExtraction($data, $file));
    }
}
