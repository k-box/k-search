<?php

namespace KCore\CoreBundle\Libraries;

use PHPUnit\Framework\TestCase;

class SolrSearchHelperTest extends TestCase
{
    public function dataProviderBuildFilterQueryForMultipleValues()
    {
        return [
            'or' => ['field:(aaa OR bbb)', 'field', 'aaa,bbb'],
            'or_single' => ['field:aaa', 'field', 'aaa,'],
            'or_single_space' => ['field:aaa', 'field', 'aaa, '],
            'or_single_quotes' => ['field:"a bb"', 'field', ' , a bb , '],

            'and' => ['field:(aaa AND bbb)', 'field', 'aaa|bbb'],
            'and_single' => ['field:aaa', 'field', 'aaa|'],
            'and_single_space' => ['field:aaa', 'field', ' | aaa | '],
            'and_single_quotes' => ['field:"a bb"', 'field', ' | a bb | '],
        ];
    }

    /**
     * @dataProvider dataProviderBuildFilterQueryForMultipleValues
     *
     * @param $expected
     * @param $field
     * @param $value
     */
    public function testBuildFilterQueryForMultipleValues($expected, $field, $value)
    {
        $this->assertEquals($expected, SolrSearchHelper::buildFilterQueryForMultipleValues($field, $value));
    }
}
