<?php

namespace App\Tests\Service;

use App\HTTP\GuzzleAdapter;
use App\Service\DataDownloaderService;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\TestCase;

class DataDownloaderServiceTest extends TestCase
{
    public function testItDownloadsData()
    {
        $data = ModelHelper::createDataModel('123');
        $fileUri = $data->url;

        $guzzle = $this->createMock(GuzzleAdapter::class);
        $sampleContent = 'this is the file contents';
        $guzzle->expects($this->once())
            ->method('requestFile')
            ->with($fileUri)
            ->willReturn($sampleContent);

        $downloader = new DataDownloaderService($guzzle);
        $contents = $downloader->getFileContents($data);

        $this->assertEquals($sampleContent, $contents);
    }
}
