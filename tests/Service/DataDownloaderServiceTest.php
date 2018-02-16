<?php

namespace App\Tests\Service;

use App\Model\Data\Data;
use App\Service\DataDownloaderService;
use GuzzleHttp\Psr7\Stream;
use Http\Message\MessageFactory;
use Http\Mock\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class DataDownloaderServiceTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const DATA_URL = 'http://example.com/my-file.txt';

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var DataDownloaderService
     */
    private $downloaderService;

    /**
     * @var MessageFactory|MockObject
     */
    private $messageFactory;

    /**
     * @var MimeTypeGuesserInterface|MockObject
     */
    private $mimeTypeGuesser;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    protected function setUp()
    {
        $this->httpClient = new Client();

        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->mimeTypeGuesser = $this->createMock(MimeTypeGuesserInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->downloaderService = new DataDownloaderService(
            $this->httpClient,
            $this->messageFactory,
            $this->mimeTypeGuesser,
            $this->filesystem,
            './path',
            $this->createMock(LoggerInterface::class)
        );
    }

    public function dataGetDataFileMimetypeFromHeadRequest(): array
    {
        return [
            ['image/jpg', ['image/jpg']],
            ['text/html', ['text/html']],
            ['text/html', ['text/html; charset=iso-8859-15']],
        ];
    }

    /**
     * @dataProvider dataGetDataFileMimetypeFromHeadRequest
     *
     * @param string $expectedMimetype
     * @param string $contentHeaders
     */
    public function testGetDataFileMimetypeFromHeadRequest(string $expectedMimetype, array $contentHeaders)
    {
        $data = $this->buildData();

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $this->mimeTypeGuesser->expects($this->never())
            ->method('guess');

        $request = $this->createMock(RequestInterface::class);
        $this->messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('HEAD', self::DATA_URL)
            ->willReturn($request);

        $response = $this->createMock(ResponseInterface::class);
        $this->httpClient->addResponse($response);

        $response->expects($this->once())
            ->method('getHeaders')
            ->willReturn([
                'Content-Type' => $contentHeaders,
            ]);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->assertSame($expectedMimetype, $this->downloaderService->getDataFileMimetype($data));
    }

    public function dataGetDataFileMimetypeMimetypeFromFile(): array
    {
        return [
            ['image/jpg'],
            ['text/html'],
        ];
    }

    /**
     * @dataProvider dataGetDataFileMimetypeMimetypeFromFile
     *
     * @param string $expectedMimetype
     * @param string $contentHeaders
     */
    public function testGetDataFileMimetypeFromFile(string $expectedMimetype)
    {
        $data = $this->buildData();

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->messageFactory->expects($this->never())
            ->method('createRequest');

        $this->mimeTypeGuesser->expects($this->once())
            ->method('guess')
            ->with($this->callback(function (string $path) {
                $this->assertStringEndsWith(DIRECTORY_SEPARATOR.self::DATA_UUID, $path);

                return true;
            }))
            ->willReturn($expectedMimetype);

        $this->assertSame($expectedMimetype, $this->downloaderService->getDataFileMimetype($data));
    }

    public function testGetDataFileWithNoDownloadedFile()
    {
        $data = $this->buildData();

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $request = $this->createMock(RequestInterface::class);
        $this->messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', self::DATA_URL)
            ->willReturn($request);

        $response = $this->createMock(ResponseInterface::class);
        $this->httpClient->addResponse($response);

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $resource = fopen('php://temp', 'w+');
        fwrite($resource, 'file contents');
        $response->expects($this->once())
           ->method('getBody')
           ->willReturn(new Stream($resource));

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($this->anything(), 'file contents');

        $file = $this->downloaderService->getDataFile($data);
        $this->assertInstanceOf(\SplFileInfo::class, $file);
    }

    public function testGetDataFileWithDownloadedFile()
    {
        $data = $this->buildData();

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->filesystem->expects($this->never())
            ->method('dumpFile');

        $this->messageFactory->expects($this->never())
            ->method('createRequest');

        $file = $this->downloaderService->getDataFile($data);
        $this->assertInstanceOf(\SplFileInfo::class, $file);
    }

    /**
     * @return Data
     */
    private function buildData(): Data
    {
        $data = new Data();
        $data->uuid = self::DATA_UUID;
        $data->url = self::DATA_URL;

        return $data;
    }
}
