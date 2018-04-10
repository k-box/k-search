<?php

namespace App\Tests\Service;

use App\Exception\FilterQuery\ParsingException;
use App\Exception\FilterQuery\UnknownPropertyException;
use App\Service\QueryService;
use PHPUnit\Framework\TestCase;

class QueryServiceTest extends TestCase
{
    /**
     * @var QueryService
     */
    private $queryService;

    protected function setUp()
    {
        $this->queryService = new QueryService();
    }

    public function testGeneratesFilterQuery()
    {
        $mapping = [
            'field.name' => 'solr_field_name',
        ];

        $this->assertSame('solr_field_name:value', $this->queryService->getFilterQuery('field.name:value', $mapping));
    }

    public function testGeneratesUnknownPropertyException()
    {
        $this->expectException(UnknownPropertyException::class);
        $mapping = [
            'field.name' => 'solr_field_name',
        ];

        $this->queryService->getFilterQuery('unknown:value', $mapping);
    }

    public function parsingExceptionDataProvider(): array
    {
        return [
            ['value'],
            ['field.name:"value'],
            ['field.name:[value TO ]'],
        ];
    }

    /**
     * @dataProvider parsingExceptionDataProvider
     */
    public function testGeneratesParsingException(string $queryString)
    {
        $this->expectException(ParsingException::class);
        $mapping = [
            'field.name' => 'solr_field_name',
        ];

        $this->queryService->getFilterQuery($queryString, $mapping);
    }
}
