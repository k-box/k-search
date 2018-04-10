<?php

namespace App\Tests\Service;

use App\Entity\AbstractSolrEntity;
use App\Entity\SolrEntityData;
use App\Service\QueryService;
use App\Service\SolrService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Solarium\Client;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;

class SolrServiceTest extends TestCase
{
    /**
     * @var SolrService
     */
    private $solrService;

    /**
     * @var Client|MockObject
     */
    private $client;

    /**
     * @var QueryService|MockObject
     */
    private $queryService;

    public function setUp()
    {
        $this->client = $this->createMock(Client::class);
        $this->queryService = $this->createMock(QueryService::class);
        $this->solrService = new SolrService(
            $this->client,
            $this->queryService,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testBuildFilterQueryFromString()
    {
        $mapping = [
            'property.name.one' => 'FIELD_ONE',
            'property.name.two' => 'FIELD_TWO',
        ];
        $filterString = 'property.name.one:myvalue';
        $key = 'my-key';

        $this->queryService->expects($this->once())
            ->method('getFilterQuery')
            ->with($filterString, $mapping)
            ->willReturn('FIELD_ONE:myvalue');

        $query = $this->solrService->buildFilterFromString($filterString, $mapping, $key);
        $this->assertInstanceOf(FilterQuery::class, $query);

        $this->assertSame($key, $query->getKey());
        $this->assertSame('FIELD_ONE:myvalue', $query->getQuery());
    }

    public function testBuildQueryByEntityType()
    {
        $this->client->expects($this->once())
            ->method('createSelect')
            ->willReturn(new Query());
        $query = $this->solrService->buildSelectQueryByEntityType(SolrEntityData::class);

        $this->assertCount(1, $query->getFilterQueries());

        /* @var FilterQuery $typeFilter */
        $typeFilter = current($query->getFilterQueries());

        $this->assertSame(SolrEntityData::FIELD_ENTITY_TYPE.':'.SolrEntityData::getEntityType(), $typeFilter->getQuery());
        $this->assertSame('entity-type', $typeFilter->getKey());
    }

    public function testSelectWithWrongTypeThrowsException()
    {
        $this->expectException(\RuntimeException::class);

        $this->solrService->buildSelectQueryByEntityType(\stdClass::class);
    }

    public function testAddWithTextExtractionThrowsExceptionIfWrongSolrType()
    {
        $this->expectException(\RuntimeException::class);

        /* @var \SplFileInfo $file|MockObject */
        $file = $this->createMock(\SplFileInfo::class);
        /* @var AbstractSolrEntity $entity|MockObject */
        $entity = $this->createMock(AbstractSolrEntity::class);

        $this->solrService->addWithTextExtraction($entity, $file);
    }
}
