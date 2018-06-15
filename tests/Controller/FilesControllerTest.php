<?php

namespace App\Tests\Controller;

use App\Exception\SolrEntityNotFoundException;
use App\Service\DataDownloader;
use App\Service\DataService;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FilesControllerTest extends WebTestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const DATA_UUID_FAIL = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var DataService|MockObject
     */
    private $dataService;

    /**
     * @var DataDownloader|MockObject
     */
    private $dataDownloader;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->dataService = $this->createMock(DataService::class);
        $this->client->getContainer()->set(DataService::class, $this->dataService);

        $this->dataDownloader = $this->createMock(DataDownloader::class);
        $this->client->getContainer()->set(DataDownloader::class, $this->dataDownloader);
    }

    public function testGetDataFileFailsIfWrongUuid(): void
    {
        $this->client->request('GET', '/files/'.self::DATA_UUID_FAIL);

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testGetDataFileFailsIfDataNotFound(): void
    {
        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willThrowException(new SolrEntityNotFoundException('Not found!'));

        $this->client->request('GET', '/files/'.self::DATA_UUID);
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testGetDataFileFailsIfDataFileNotFound(): void
    {
        $data = ModelHelper::createDataModel(self::DATA_UUID);

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $this->dataDownloader->expects($this->once())
            ->method('dataFileExistsAndIsCurrent')
            ->with($data)
            ->willReturn(null);

        $this->client->request('GET', '/files/'.self::DATA_UUID);

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertTrue($response->isRedirect($data->url));
    }

    public function testGetDataFileSucceeds(): void
    {
        $data = ModelHelper::createDataModel(self::DATA_UUID);

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $fileName = __DIR__.'/../fixtures/example.txt';
        $this->dataDownloader->expects($this->once())
            ->method('dataFileExistsAndIsCurrent')
            ->with($data)
            ->willReturn($fileName);

        $this->client->request('GET', '/files/'.self::DATA_UUID);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($data->hash, $response->headers->get('E-Tag'));

        $this->assertSame(
            'attachment; filename="'.$data->properties->filename.'"',
            $response->headers->get('Content-Disposition')
        );

        // Enable the output buffer
        ob_start();
        // Send the response to the output buffer
        $response->sendContent();
        // Get the contents of the output buffer
        $content = ob_get_contents();
        // Clean the output buffer and end it
        ob_end_clean();

        $this->assertStringEqualsFile($fileName, $content);
    }
}
