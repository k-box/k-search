<?php

namespace App\Tests\Service;

use App\Entity\SolrEntityData;
use App\Exception\BadRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\GeoJson\ModelFactory;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Model\Data\Search\GeoLocationFilter;
use App\Queue\Message\DataDownloadMessage;
use App\Service\DataDownloader;
use App\Service\DataProcessingService;
use App\Service\DataService;
use App\Service\DataStatusService;
use App\Service\KlinkService;
use App\Service\SolrService;
use App\Tests\Helper\TestModelHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Solarium\Component\EdisMax;
use Solarium\Component\Facet\Field;
use Solarium\Component\FacetSet;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataServiceTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const LATEST_VERSION = '3.7';

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
     * @var DataProcessingService|MockObject
     */
    private $dataProcessingService;

    /**
     * @var DataDownloader|MockObject
     */
    private $downloaderService;

    /**
     * @var MessageBusInterface|MockObject
     */
    private $messageBus;

    /**
     * @var DataStatusService|MockObject
     */
    private $dataStatusService;

    /**
     * @var KlinkService|MockObject
     */
    private $klinkService;

    protected function setUp()
    {
        parent::setUp();
        $this->solrService = $this->createMock(SolrService::class);
        $this->dataStatusService = $this->createMock(DataStatusService::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->query = $this->createMock(Query::class);
        $this->edisMax = $this->createMock(EdisMax::class);
        $this->facetSet = $this->createMock(FacetSet::class);
        $this->dataProcessingService = $this->createMock(DataProcessingService::class);
        $this->downloaderService = $this->createMock(DataDownloader::class);
        $this->klinkService = $this->createMock(KlinkService::class);
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
     */
    public function testHasIndexableContentType(string $contentType): void
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

    public function testHasNotIndexableContentType(): void
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

    public function testHasNoContentTypeHeaders(): void
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

    public function testDeleteDataSucceeds(): void
    {
        $this->solrService->expects($this->once())
            ->method('delete')
            ->with(SolrEntityData::getEntityType(), self::DATA_UUID)
            ->willReturn(true);

        $this->downloaderService->expects($this->once())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID)
            ->willReturn(true);
        $this->downloaderService->expects($this->once())
            ->method('removeStoredTextualContents')
            ->with(self::DATA_UUID)
            ->willReturn(true);

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->deleteData(self::DATA_UUID));
    }

    public function testDeleteDataWithFileDeletionFailureSucceeds(): void
    {
        $this->solrService->expects($this->once())
            ->method('delete')
            ->with(SolrEntityData::getEntityType(), self::DATA_UUID)
            ->willReturn(true);

        $this->downloaderService->expects($this->once())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID)
            ->willReturn(false);
        $this->downloaderService->expects($this->once())
            ->method('removeStoredTextualContents')
            ->with(self::DATA_UUID)
            ->willReturn(false);

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->deleteData(self::DATA_UUID));
    }

    public function testDeleteDataWithNotExistingDataFails(): void
    {
        $this->solrService->expects($this->once())
            ->method('delete')
            ->with(SolrEntityData::getEntityType(), self::DATA_UUID)
            ->willReturn(false);

        $this->downloaderService->expects($this->never())
            ->method('removeDownloadedDataFile');
        $this->downloaderService->expects($this->never())
            ->method('removeStoredTextualContents');

        $dataService = $this->buildDataService();
        $this->assertFalse($dataService->deleteData(self::DATA_UUID));
    }

    public function testAddDataWithTextualContentSucceeds(): void
    {
        $sampleTextualContent = 'example indeaxable content';
        $data = TestModelHelper::createDataModel(self::DATA_UUID);

        $this->solrService->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(function (SolrEntityData $data) {
                    $this->assertEquals(DataStatus::STATUS_INDEX_OK, $data->getField(SolrEntityData::FIELD_STATUS));

                    return true;
                }))
            ->willReturn(true);

        $this->dataProcessingService->expects($this->never())
            ->method('addDataForProcessing');

        $this->downloaderService->expects($this->once())
            ->method('storeDataTextualContents')
            ->with($data->uuid, $sampleTextualContent);

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                $this->assertInstanceOf(DataDownloadMessage::class, $message);

                /* @var $message DataDownloadMessage */
                $this->assertSame(self::DATA_UUID, $message->getUuid());

                return true;
            }));

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->addData($data, $sampleTextualContent));
    }

    public function testAddDataWithTextualContentSucceedsNoDownloadForVideoType(): void
    {
        $sampleTextualContent = 'example indeaxable content';
        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $data->type = Data::DATA_TYPE_VIDEO;

        $this->solrService->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(function (SolrEntityData $data) {
                    $this->assertEquals(DataStatus::STATUS_INDEX_OK, $data->getField(SolrEntityData::FIELD_STATUS));

                    return true;
                }))
            ->willReturn(true);

        $this->dataProcessingService->expects($this->never())
            ->method('addDataForProcessing');

        $this->downloaderService->expects($this->once())
            ->method('storeDataTextualContents')
            ->with($data->uuid, $sampleTextualContent);

        $this->messageBus->expects($this->never())
            ->method('dispatch');

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
     */
    public function testThrowsExceptionIfDataIsNotIndexable(?string $textContents, string $type): void
    {
        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $data->type = $type;

        $this->dataProcessingService->expects($this->never())
            ->method('addDataForProcessing');

        $this->downloaderService->expects($this->never())
            ->method('storeDataTextualContents');

        $this->messageBus->expects($this->never())
            ->method('dispatch');

        $this->expectException(BadRequestException::class);
        $dataService = $this->buildDataService();
        $dataService->addData($data, $textContents);
    }

    public function testItQueuesIndexableData(): void
    {
        $data = TestModelHelper::createDataModel(self::DATA_UUID);

        $this->solrService->expects($this->never())
            ->method('add');

        $this->downloaderService->expects($this->never())
            ->method('storeDataTextualContents');

        $this->messageBus->expects($this->never())
            ->method('dispatch');

        $this->dataProcessingService->expects($this->once())
            ->method('addDataForProcessing')
            ->with($data);

        $this->downloaderService->expects($this->once())
            ->method('getDataFileMimetype')
            ->willReturn('image/jpg');

        $dataService = $this->buildDataService();
        $this->assertTrue($dataService->addData($data));
    }

    public function testAddDataWithFileExtractionAndDataRetention(): void
    {
        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        /**
         * @var SplFileInfo|MockObject
         */
        $file = $this->createMock(SplFileInfo::class);

        $this->solrService->expects($this->once())
            ->method('addWithTextExtraction')
            ->with(
                $this->callback(function (SolrEntityData $data) {
                    $this->assertEquals(DataStatus::STATUS_INDEX_OK, $data->getField(SolrEntityData::FIELD_STATUS));

                    return true;
                }),
                $this->callback(function (\SplFileInfo $file) {
                    return true;
                }))
            ->willReturn(true);

        $this->dataProcessingService->expects($this->never())
            ->method('addDataForProcessing');

        $this->downloaderService->expects($this->never())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID);

        $this->downloaderService->expects($this->never())
            ->method('storeDataTextualContents');

        $this->messageBus->expects($this->never())
            ->method('dispatch');

        $this->downloaderService->expects($this->once())
            ->method('removeStoredTextualContents')
            ->with(self::DATA_UUID);

        $dataService = $this->buildDataService(self::TYPES);
        $this->assertTrue($dataService->addDataWithFileExtraction($data, $file));
    }

    public function testAddDataWithFileExtractionAndNoDataRetention(): void
    {
        $data = TestModelHelper::createDataModel(self::DATA_UUID);
        $file = $this->createMock(SplFileInfo::class);

        $this->solrService->expects($this->once())
            ->method('addWithTextExtraction')
            ->with(
                $this->callback(function (SolrEntityData $data) {
                    $this->assertEquals(DataStatus::STATUS_INDEX_OK, $data->getField(SolrEntityData::FIELD_STATUS));

                    return true;
                }),
                $this->callback(function (\SplFileInfo $file) {
                    return true;
                }))
            ->willReturn(true);

        $this->dataProcessingService->expects($this->never())
            ->method('addDataForProcessing');

        $this->downloaderService->expects($this->once())
            ->method('removeDownloadedDataFile')
            ->with(self::DATA_UUID);

        $this->downloaderService->expects($this->once())
            ->method('removeStoredTextualContents')
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
    public function testSearchDataWithAggregationsWithFilteredCounts(bool $expectedExcludeFilter, bool $filteredValue): void
    {
        $facetField = $this->createMock(Field::class);
        $searchParam = TestModelHelper::createDataSearchParamsModel();
        $agg = TestModelHelper::createDataSearchParamAggregationModel();
        $agg->countsFiltered = $filteredValue;

        $aggregationField = 'uploader.name';
        $aggregationSolrField = SolrEntityData::getAggregationFields()[$aggregationField];
        $searchParam->aggregations[$aggregationField] = $agg;
        $searchParam->search = 'search-terms';

        $this->setupSolrServiceForSearch([
            'search' => 'search-terms',
            'facets' => true,
        ]);

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

    public function testGetDataNoResults(): void
    {
        $this->solrService->expects($this->once())
            ->method('buildFilterQuery')
            ->with(SolrEntityData::FIELD_ENTITY_ID, self::DATA_UUID, 'id');

        $resultSet = $this->createMock(Result::class);
        $this->solrService->expects($this->once())
            ->method('getByFilter')
            ->willReturn($resultSet);
        $resultSet->expects($this->once())
            ->method('getNumFound')
            ->willReturn(0);

        $dataService = $this->buildDataService();

        $this->expectException(SolrEntityNotFoundException::class);
        $dataService->getData(self::DATA_UUID);
    }

    public function testSearchDataWithSorts(): void
    {
        $searchParam = TestModelHelper::createDataSearchParamsModel();
        $searchParam->sort = [TestModelHelper::createDataSearchParamSort([
            'field' => 'properties.title',
            'order' => 'desc',
        ])];
        $searchParam->search = 'search-terms';

        $this->setupSolrServiceForSearch([
            'search' => 'search-terms',
        ]);

        $this->query->expects($this->once())
            ->method('setSorts')
            ->with($this->callback(function (array $sorts) {
                $this->assertCount(1, $sorts);
                $this->assertArrayHasKey(SolrEntityData::FIELD_PROPERTIES_TITLE_SORTING, $sorts);
                $this->assertSame('desc', current($sorts));

                return true;
            }));

        $dataService = $this->buildDataService();
        $dataService->searchData($searchParam, '3.2');
    }

    public function testSearchDataWithAllKlinks(): void
    {
        $searchParam = TestModelHelper::createDataSearchParamsModel();
        $searchParam->klinkFilters = '*';
        $searchParam->search = 'search-terms';

        $this->setupSolrServiceForSearch([
            'search' => 'search-terms',
        ]);

        $this->klinkService->expects($this->once())
            ->method('klinkIdentifiers')
            ->willReturn(['1']);

        $this->query->expects($this->once())
            ->method('addFilterQuery');

        $dataService = $this->buildDataService();
        $dataService->searchData($searchParam, '3.7');
    }

    public function testSearchDataWithKlinks(): void
    {
        $searchParam = TestModelHelper::createDataSearchParamsModel();
        $searchParam->klinkFilters = '1';
        $searchParam->search = 'search-terms';

        $this->setupSolrServiceForSearch([
            'search' => 'search-terms',
        ]);

        $this->klinkService->expects($this->once())
            ->method('ensureValidKlinks')
            ->with(['1'])
            ->willReturn(['1']);

        $this->query->expects($this->once())
            ->method('addFilterQuery');

        $dataService = $this->buildDataService();
        $dataService->searchData($searchParam, '3.7');
    }

    public function providerSearchDataWithAggregationsWithVersion(): array
    {
        return [
            'version3-2' => ['3.2', 1],
            'version3-1' => ['3.1', 0],
            'latest' => [self::LATEST_VERSION, 1],
        ];
    }

    /**
     * @dataProvider providerSearchDataWithAggregationsWithVersion
     */
    public function testSearchDataWithAggregationsWithVersion(string $version, int $minCount): void
    {
        $facetField = $this->createMock(Field::class);
        $searchParam = TestModelHelper::createDataSearchParamsModel();
        $agg = TestModelHelper::createDataSearchParamAggregationModel();

        $aggregationField = 'uploader.name';
        $aggregationSolrField = SolrEntityData::getAggregationFields()[$aggregationField];
        $searchParam->aggregations[$aggregationField] = $agg;
        $searchParam->search = 'search-terms';

        $this->setupSolrServiceForSearch([
            'search' => 'search-terms',
            'facets' => true,
        ]);

        $this->solrService->expects($this->once())
            ->method('buildFacet')
            ->with($aggregationSolrField, 10, $minCount, $aggregationField)
            ->willReturn($facetField);

        $dataService = $this->buildDataService();
        $dataService->searchData($searchParam, $version);
    }

    public function testSearchWithGeoFilters(): void
    {
        $polygon = '{"type": "Polygon", "coordinates": [[[100,0],[101,0],[101,1],[100,1],[100,0]]]}';
        $filter = new GeoLocationFilter();
        $filter->boundingBox = $polygon;

        $searchParam = TestModelHelper::createDataSearchParamsModel();
        $searchParam->search = 'search-terms';
        $searchParam->geoLocationFilter = $filter;

        $this->setupSolrServiceForSearch(['search' => 'search-terms']);

        $this->solrService->expects($this->once())
            ->method('buildPolygonIntersectFilter')
            ->with(SolrEntityData::FIELD_GEO_LOCATION, $polygon = ModelFactory::buildFromJson($polygon));

        $dataService = $this->buildDataService();
        $dataService->searchData($searchParam, self::LATEST_VERSION);
    }

    private function buildDataService(array $types = self::TYPES, bool $retainDownloads = true): DataService
    {
        return new DataService(
            $this->dataProcessingService,
            $this->dataStatusService,
            $this->solrService,
            $this->downloaderService,
            $this->messageBus,
            $types,
            $retainDownloads,
            $this->createMock(LoggerInterface::class),
            $this->klinkService
        );
    }

    private function setupSolrServiceForSearch(array $data)
    {
        $resolver = (new OptionsResolver())
            ->setRequired([
                'search',
            ])
            ->setDefaults([
                'facets' => false,
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

        // Handling facets
        $this->query->expects($this->exactly(true === $options['facets'] ? 1 : 0))
            ->method('getFacetSet')
            ->willReturn($this->facetSet);
        $this->facetSet->expects($this->exactly(true === $options['facets'] ? 1 : 0))
            ->method('addFacets');

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
