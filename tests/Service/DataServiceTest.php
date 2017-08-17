<?php

namespace App\Tests\Service;

use App\Helper\DataHelper;
use App\Manager\DataManager;
use App\Service\DataService;
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

        $dataHelper = $this->createMock(DataHelper::class);

        $dataManager = $this->createMock(DataManager::class);

        $solrServiceMock->expects($this->exactly(2))
            ->method('delete')
            ->willReturnOnConsecutiveCalls(true, false);

        $dataService = new DataService($solrServiceMock, $dataHelper, $dataManager);

        $this->assertTrue($dataService->deleteData('existing-uuid'));
        $this->assertFalse($dataService->deleteData('uneexisting-uuid'));
    }

    public function testItAddsData() {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleTextualContent = 'example indeaxable content';

        $solrServiceMock = $this->getMockBuilder(SolrService::class)
            ->disableOriginalConstructor()
            ->getMock();


        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('isIndexable')
            ->willReturn(true);

        $dataManager = $this->createMock(DataManager::class);
        $dataManager->expects($this->once())
            ->method( 'handleIndexableDataAdding')
            ->willReturn(true);

        $dataService = new DataService($solrServiceMock, $dataHelper, $dataManager);
        $dataService->addData( ModelHelper::createDataModel($sampleUUID), $sampleTextualContent);
    }


}
