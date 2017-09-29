<?php

namespace App\Tests\Service;

use App\Entity\SolrEntityData;
use App\Model\Data\SearchParams;
use App\Service\SolrService;
use PHPUnit\Framework\TestCase;
use Solarium\Client;

class SolrServiceTest extends TestCase
{
    /** @var SolrService */
    private $solrService;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $client;

    public function setUp()
    {
        $this->client = $this->createMock(Client::class);
        $this->solrService = new SolrService($this->client);
    }

    public function testItReplacesTheAliases()
    {
        $searchParams = new SearchParams();
        $searchParams->search = 'test string';
        $searchParams->filters = 'properties.language:es';

        $select = new \Solarium\QueryType\Select\Query\Query();

        $this->client->expects($this->once())
            ->method('createSelect')
            ->willReturn($select);

        $this->client->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($select) {
                $expectedFilter = sprintf('%s:es', SolrEntityData::FIELD_INDEXABLE_LANGUAGE);
                $this->assertEquals($expectedFilter, $select->getFilterQuery(SolrService::USER_FILTER_KEY)->getQuery());

                return true;
            }))
            ->willReturn($this->createMock(\Solarium\QueryType\Select\Result\Result::class));

        $this->solrService->select($searchParams, SolrEntityData::class);
    }
}
