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

    public function filterQueryDataprovider(): array
    {
        return [
            ['solr_field_name1:value', 'field.name1:value'],
            [
                '(solr_field_name1:value11 OR solr_field_name1:value12) AND solr_field_name2:value2',
                '(field.name1:value11 OR field.name1:value12) AND field.name2:value2',
            ],
        ];
    }

    /**
     * @dataProvider filterQueryDataprovider
     */
    public function testGeneratesFilterQuery(string $expectedQuery, string $filter)
    {
        $mapping = [
            'field.name1' => 'solr_field_name1',
            'field.name2' => 'solr_field_name2',
            'field.name3' => 'solr_field_name3',
        ];

        $this->assertSame($expectedQuery, $this->queryService->getFilterQuery($filter, $mapping));
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
