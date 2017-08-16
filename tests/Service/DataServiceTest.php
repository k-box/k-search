<?php

namespace App\Tests\Service;

use App\Service\DataService;
use App\Service\SolrService;
use PHPUnit\Framework\TestCase;

class DataServiceTest extends TestCase {


    public function testItDeletesData()
    {
        $solrServiceMock = $this->getMockBuilder(SolrService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $solrServiceMock->expects($this->exactly(2))
            ->method('delete')
            ->willReturnOnConsecutiveCalls(true, false);


        $dataService = new DataService($solrServiceMock);

        $this->assertTrue($dataService->deleteData('existing-uuid'));
        $this->assertFalse($dataService->deleteData('uneexisting-uuid'));
    }

}