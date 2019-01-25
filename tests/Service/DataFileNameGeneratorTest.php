<?php

namespace App\Tests\Service;

use App\Service\DataFileNameGenerator;
use PHPUnit\Framework\TestCase;

class DataFileNameGeneratorTest extends TestCase
{
    private const DOWNLOAD_PATH = 'path';
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';

    /**
     * @var DataFileNameGenerator
     */
    private $generator;

    protected function setUp()
    {
        $this->generator = new DataFileNameGenerator(self::DOWNLOAD_PATH);
    }

    public function buildNameDataprovider(): array
    {
        return [
            [self::DOWNLOAD_PATH.\DIRECTORY_SEPARATOR.'cc'.\DIRECTORY_SEPARATOR.'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd', self::DATA_UUID, null],
            [self::DOWNLOAD_PATH.\DIRECTORY_SEPARATOR.'cc'.\DIRECTORY_SEPARATOR.'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd', self::DATA_UUID, ''],
            [self::DOWNLOAD_PATH.\DIRECTORY_SEPARATOR.'cc'.\DIRECTORY_SEPARATOR.'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd.contents', self::DATA_UUID, 'contents'],
        ];
    }

    /**
     * @dataProvider buildNameDataprovider
     */
    public function testBuildName(string $expected, string $uuid, ?string $ext)
    {
        $this->assertSame($expected, $this->generator->buildDownloadDataFilename($uuid, $ext));
    }
}
