<?php

namespace App\Tests\Service;

use App\Entity\SolrEntityData;
use App\Exception\BadRequestException;
use App\Model\Data\Data;
use App\Queue\Message\UUIDMessage;
use App\Service\DataDownloader;
use App\Service\DataService;
use App\Service\QueueService;
use App\Service\SolrService;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Solarium\QueryType\Select\Query\Component\EdisMax;
use Solarium\QueryType\Select\Query\Component\Facet\Field;
use Solarium\QueryType\Select\Query\Component\FacetSet;
use Solarium\QueryType\Select\Query\Query;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataServiceTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';

    private const TYPES = [
            'application/pdf',
            'image/jpg',
            'text/html',
        ];

    /**
     * @var SolrService|MockObject
     */
    private $solrService;

    /**
     * @var Query|MockObject
     */
    private $query;

    /**
     * @var EdisMax|MockObject
     */
    private $edisMax;

    /**
     * @var FacetSet|MockObject
     */
    private $facetSet;

    /**
     * @var QueueService|MockObject
     */
    private $queueService;

    /**
     * @var DataDownloader|MockObject
     */
    private $downloaderService;

    protected function setUp()
    {
        parent::setUp();
        $this->solrService = $this->createMock(SolrService::class);
        $this->query = $this->createMock(Query::class);
        $this->edisMax = $this->createMock(EdisMax::class);
        $this->facetSet = $this->createMock(FacetSet::class);
        $this->queueService = $this->createMock(QueueService::class);
        $this->downloaderService = $this->createMock(DataDownloader::class);
    }

    public function provideIndexableContentTypes(): array
    {
        return [
            ['image/jpg'],
            ['text/html'],
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
            ->method('getDataFileMimetype')
            ->with($this->callback(function (Data $data) {
                $this->assertSame($data->url, 'http://someurls.com/data.ext');

                return true;
            }))
            ->willReturn($contentType);

        $dataService = $this->buildDataService();
        $dataService->ensureDataIsIndexable($data);
    }

    public function testHasNotIndexableContentType()
    {
        $data = new Data();
        $data->url = 'http://someurls.com/data.ext';

        $this->downloaderService->expects($this->once())
            ->method('getDataFileMimetype')
            ->willReturn('image/jpeg2000');

        $this->expectException(BadRequestException::class);
        $dataService = $this->buildDataService();
        $dataService->ensureDataIsIndexable($data);
    }

    public function testHasNoContentTypeHeaders()
    {
        $data = new Data();
        $data->url = 'http://someurls.com/data.ext';

        $this->downloaderService->expects($this->once())
            ->method('getDataFileMimetype')
            ->willReturn(null);
        $this->expectException(BadRequestException::class);

        $dataService = $this->buildDataService();
        $dataService->ensureDataIsIndexable($data);
    }

    public function testDeleteDataSucceeds()
    {
        $this->solrService->expects($this->once())
            ->method('delete')
            ->with(SolrEntityData::getEntityType(), self::DATA_UUID)
            ->willReturn(true);

        $this->downloaderService->expects($this->once())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID)
            ->willReturn(true);

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->deleteData(self::DATA_UUID));
    }

    public function testDeleteDataWithFileDeletionFailureSucceeds()
    {
        $this->solrService->expects($this->once())
            ->method('delete')
            ->with(SolrEntityData::getEntityType(), self::DATA_UUID)
            ->willReturn(true);

        $this->downloaderService->expects($this->once())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID)
            ->willReturn(false);

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->deleteData(self::DATA_UUID));
    }

    public function testDeleteDataWithNotExistingDataFails()
    {
        $this->solrService->expects($this->once())
            ->method('delete')
            ->with(SolrEntityData::getEntityType(), self::DATA_UUID)
            ->willReturn(false);

        $this->downloaderService->expects($this->never())
            ->method('removeDownloadedDataFile');

        $dataService = $this->buildDataService();
        $this->assertFalse($dataService->deleteData(self::DATA_UUID));
    }

    public function testAddDataWithTextualContentSucceeds()
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

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
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
        $dataService = $this->buildDataService();
        $dataService->addData($data, $textContents);
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
            ->method('getDataFileMimetype')
            ->willReturn('image/jpg');

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->addData($data));
    }

    public function testAddDataWithFileExtractionAndDataRetention()
    {
        $data = ModelHelper::createDataModel(self::DATA_UUID);
        /**
         * @var SplFileInfo|MockObject
         */
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

        $this->downloaderService->expects($this->never())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID);

        $dataService = $this->buildDataService(self::TYPES);
        $this->assertTrue($dataService->addDataWithFileExtraction($data, $file));
    }

    public function testAddDataWithFileExtractionAndNoDataRetention()
    {
        $data = ModelHelper::createDataModel(self::DATA_UUID);
        /**
         * @var SplFileInfo|MockObject
         */
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

        $this->downloaderService->expects($this->once())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID);

        $dataService = $this->buildDataService(self::TYPES, false);
        $this->assertTrue($dataService->addDataWithFileExtraction($data, $file));
    }

    public function providerSearchDataWithAggregationsWithFilteredCounts(): array
    {
        return [
            // The default: facets are computed before filtering (thus: filters are excluded)
            'default' => [false, true],

            // Filtered: facets are computed after filtering, this normal "solr" behavior
            'filtered' => [true, false],
        ];
    }

    /**
     * @dataProvider providerSearchDataWithAggregationsWithFilteredCounts
     */
    public function testSearchDataWithAggregationsWithFilteredCounts(bool $expectedExcludeFilter, bool $filteredValue)
    {
        $facetField = $this->createMock(Field::class);
        $searchParam = ModelHelper::createDataSearchParamsModel();
        $agg = ModelHelper::createDataSearchParamAggregationModel();
        $agg->countsFiltered = $filteredValue;

        $aggregationField = 'uploader.name';
        $aggregationSolrField = SolrEntityData::getAggregationFields()[$aggregationField];
        $searchParam->aggregations[$aggregationField] = $agg;
        $searchParam->search = 'search-terms';

        $this->setupSolrServiceForSearch(['search' => 'search-terms']);

        $this->query->expects($this->never())
            ->method('addSorts');

        $this->facetSet->expects($this->once())
            ->method('addFacets');

        $this->solrService->expects($this->once())
            ->method('buildFacet')
            ->with($aggregationSolrField, 10, 1, $aggregationField)
            ->willReturn($facetField);

        $facetField->expects($this->exactly($expectedExcludeFilter ? 1 : 0))
            ->method('setExcludes');

        $dataService = $this->buildDataService();
        $dataService->searchData($searchParam, '3.2');
    }

    public function providerSearchDataWithAggregationsWithVersion(): array
    {
        return [
            'version3-2' => ['3.2', 1],
            'version3-1' => ['3.1', 0],
        ];
    }

    /**
     * @dataProvider providerSearchDataWithAggregationsWithVersion
     */
    public function testSearchDataWithAggregationsWithVersion(string $version, int $minCount)
    {
        $facetField = $this->createMock(Field::class);
        $searchParam = ModelHelper::createDataSearchParamsModel();
        $agg = ModelHelper::createDataSearchParamAggregationModel();

        $aggregationField = 'uploader.name';
        $aggregationSolrField = SolrEntityData::getAggregationFields()[$aggregationField];
        $searchParam->aggregations[$aggregationField] = $agg;
        $searchParam->search = 'search-terms';

        $this->setupSolrServiceForSearch(['search' => 'search-terms']);

        $this->query->expects($this->never())
            ->method('addSorts');

        $this->facetSet->expects($this->once())
            ->method('addFacets');

        $this->solrService->expects($this->once())
            ->method('buildFacet')
            ->with($aggregationSolrField, 10, $minCount, $aggregationField)
            ->willReturn($facetField);

        $dataService = $this->buildDataService();
        $dataService->searchData($searchParam, $version);
    }

    private function buildDataService(array $types = self::TYPES, bool $retainDownloads = true): DataService
    {
        return new DataService(
            $this->queueService,
            $this->solrService,
            $this->downloaderService,
            $types,
            $retainDownloads,
            $this->createMock(LoggerInterface::class)
        );
    }

    private function setupSolrServiceForSearch(array $data)
    {
        $resolver = (new OptionsResolver())
            ->setRequired([
                'search',
            ])
            ->setDefaults([
                'start' => 0,
                'limit' => 10,
            ]);
        $options = $resolver->resolve($data);

        $this->solrService->expects($this->once())
            ->method('buildSelectQueryByEntityType')
            ->with(SolrEntityData::class, 'entity-type')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getEDisMax')
            ->willReturn($this->edisMax);

        $this->query->expects($this->once())
            ->method('getFacetSet')
            ->willReturn($this->facetSet);

        $this->query->expects($this->once())
            ->method('setRows')
            ->with($options['limit']);

        $this->query->expects($this->once())
            ->method('setStart')
            ->with($options['start']);

        $this->query->expects($this->once())
            ->method('setQuery')
            ->with($options['search']);
    }
}
