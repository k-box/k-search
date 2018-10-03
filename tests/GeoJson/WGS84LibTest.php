<?php

namespace App\Tests\GeoJson;

use App\GeoJson\Model\Position;
use App\GeoJson\WGS84Lib;
use PHPUnit\Framework\TestCase;

class WGS84LibTest extends TestCase
{
    public function isValidPositionDataProvider(): iterable
    {
        yield [true, Position::build(0.0, 0.0)];
        yield [true, Position::build(180.0, 90.0)];
        yield [true, Position::build(180.0, -90)];
        yield [true, Position::build(-180, 90)];
        yield [true, Position::build(-180, -90)];

        yield [false, Position::build(-180.1, 0)];
        yield [false, Position::build(0, -90.1)];
        yield [false, Position::build(-180.1, -90.1)];
    }

    /**
     * @dataProvider  isValidPositionDataProvider
     */
    public function testIsValidPosition(bool $expected, Position $position): void
    {
        $this->assertSame($expected, WGS84Lib::isValidPosition($position));
    }
}
