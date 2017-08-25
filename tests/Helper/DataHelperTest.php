<?php

namespace App\Tests\Helper;

use App\Helper\DataHelper;
use PHPUnit\Framework\TestCase;

class DataHelperTest extends TestCase
{
    /**
     * @dataProvider indexableTypes
     *
     * @param mixed $type
     */
    public function testItKnowsWhichDataObjectsAreIndexable($type)
    {
        $data = ModelHelper::createDataModel('123');

        $data->type = $type;
        $this->assertTrue(DataHelper::isIndexable($data));
    }

    public function indexableTypes()
    {
        return [
            ['document'],
        ];
    }

    /**
     * @dataProvider notIndexableTypes
     *
     * @param mixed $type
     */
    public function testItKnowsWhichDataObjectsAreNotIndexable($type)
    {
        $data = ModelHelper::createDataModel('123');

        $data->type = $type;
        $this->assertFalse(DataHelper::isIndexable($data));
    }

    public function notIndexableTypes()
    {
        return [
            ['video'],
        ];
    }
}
