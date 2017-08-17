<?php

namespace App\Tests\Service;

use App\Entity\SolrEntityData;
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

    public function testItAddsDataWithTextualContent()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleTextualContent = 'example indeaxable content';
        $data = ModelHelper::createDataModel($sampleUUID);

        $solrServiceMock = $this->getMockBuilder(SolrService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $solrServiceMock->expects($this->once())
            ->method('add')
            ->with($this->callback(function (SolrEntityData $solrEntity) {
                return !empty($solrEntity->getField('str_ss_data_textual_content'));
            }))
            ->willReturn(true);

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->never())
            ->method('isIndexable');

        $dataManager = $this->createMock(DataManager::class);
        $dataManager->expects($this->never())
            ->method('handleIndexableDataAddition');

        $dataService = new DataService($solrServiceMock, $dataHelper, $dataManager);
        $dataService->addData($data, $sampleTextualContent);
    }
}
