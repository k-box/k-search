<?php

namespace App\Tests\Service;

use App\Exception\DataDownloadErrorException;
use App\Model\Data\Data;
use App\Service\DataDownloader;
use App\Service\DataFileNameGenerator;
use GuzzleHttp\Psr7\Stream;
use Http\Client\Exception\RequestException;
use Http\Message\MessageFactory;
use Http\Mock\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class DataDownloaderTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const DATA_URL = 'http://example.com/my-file.txt';
    private const DATA_TEMP_FILENAME = './var/cache/test/data-temp-uuid';
    private const DATA_CONTENTS_FILENAME = './var/cache/test/data-temp-uuid.contents';
    private const DATA_FILE_CONTENTS = 'file contents';

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var DataDownloader
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

    /**
     * @var DataFileNameGenerator|MockObject
     */
    private $nameGenerator;

    protected function setUp()
    {
        $this->httpClient = new Client();

        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->mimeTypeGuesser = $this->createMock(MimeTypeGuesserInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->nameGenerator = $this->createMock(DataFileNameGenerator::class);

        $this->downloaderService = new DataDownloader(
            $this->httpClient,
            $this->messageFactory,
            $this->mimeTypeGuesser,
            $this->filesystem,
            $this->nameGenerator,
            $this->createMock(LoggerInterface::class)
        );
    }

    protected function tearDown()
    {
        @unlink(self::DATA_TEMP_FILENAME);
    }

    public function dataGetDataFileMimetypeFromHeadRequest(): array
    {
        return [
            ['image/jpg', ['image/jpg']],
            ['text/html', ['text/html']],
            ['text/html', ['text/html; charset=iso-8859-15']],
            [null, []],
            [null, null],
        ];
    }

    /**
     * @dataProvider dataGetDataFileMimetypeFromHeadRequest
     */
    public function testGetDataFileMimetypeFromHeadRequest(?string $expectedMimetype, ?array $contentHeaders)
    {
        $data = $this->buildData();

        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
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
            ->willReturn($contentHeaders ? ['Content-Type' => $contentHeaders] : []);
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
     */
    public function testGetDataFileMimetypeFromFile(string $expectedMimetype)
    {
        $data = $this->buildData();
        $data->hash = $this->prepareTempDataFile();

        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(true);

        $this->messageFactory->expects($this->never())
            ->method('createRequest');

        $this->mimeTypeGuesser->expects($this->once())
            ->method('guess')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn($expectedMimetype);

        $this->assertSame($expectedMimetype, $this->downloaderService->getDataFileMimetype($data));
    }

    public function testGetDataFileMimetypeFromFileThrowsException()
    {
        $data = $this->buildData();
        $data->hash = $this->prepareTempDataFile();

        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(true);

        $this->messageFactory->expects($this->never())
            ->method('createRequest');

        $this->mimeTypeGuesser->expects($this->once())
            ->method('guess')
            ->with(self::DATA_TEMP_FILENAME)
            ->willThrowException(new \Exception());

        $this->assertNull($this->downloaderService->getDataFileMimetype($data));
    }

    public function testRemoveDownloadedDataFile()
    {
        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with(self::DATA_UUID, null)
            ->willReturn(self::DATA_CONTENTS_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willReturn(true);

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with(self::DATA_CONTENTS_FILENAME);

        $this->assertTrue($this->downloaderService->removeDownloadedDataFile(self::DATA_UUID));
    }

    public function testRemoveDownloadedDataFileNoFile()
    {
        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with(self::DATA_UUID, null)
            ->willReturn(self::DATA_CONTENTS_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willReturn(false);

        $this->filesystem->expects($this->never())
            ->method('remove');

        $this->assertFalse($this->downloaderService->removeDownloadedDataFile(self::DATA_UUID));
    }

    public function testRemoveDownloadedDataFileExceptionIsCatch()
    {
        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with(self::DATA_UUID, null)
            ->willReturn(self::DATA_CONTENTS_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willReturn(true);

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willThrowException(new IOException(''));

        $this->assertFalse($this->downloaderService->removeDownloadedDataFile(self::DATA_UUID));
    }

    public function testStoredTextualContents()
    {
        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with(self::DATA_UUID, 'contents')
            ->willReturn(self::DATA_CONTENTS_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with(self::DATA_CONTENTS_FILENAME, self::DATA_FILE_CONTENTS);

        $this->downloaderService->storeDataTextualContents(self::DATA_UUID, self::DATA_FILE_CONTENTS);
    }

    public function testRemoveStoredTextualContents()
    {
        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with(self::DATA_UUID, 'contents')
            ->willReturn(self::DATA_CONTENTS_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willReturn(true);

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with(self::DATA_CONTENTS_FILENAME);

        $this->assertTrue($this->downloaderService->removeStoredTextualContents(self::DATA_UUID));
    }

    public function testRemoveStoredTextualContentsNoFile()
    {
        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with(self::DATA_UUID, 'contents')
            ->willReturn(self::DATA_CONTENTS_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willReturn(false);

        $this->filesystem->expects($this->never())
            ->method('remove');

        $this->assertFalse($this->downloaderService->removeStoredTextualContents(self::DATA_UUID));
    }

    public function testRemoveStoredTextualContentsExceptionIsCatch()
    {
        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with(self::DATA_UUID, 'contents')
            ->willReturn(self::DATA_CONTENTS_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willReturn(true);

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with(self::DATA_CONTENTS_FILENAME)
            ->willThrowException(new IOException(''));

        $this->assertFalse($this->downloaderService->removeStoredTextualContents(self::DATA_UUID));
    }

    public function testGetDataFileWithDownloadHttpException()
    {
        $data = $this->buildData();

        $this->nameGenerator->expects($this->exactly(1))
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(false);

        $request = $this->mockGetRequest(self::DATA_URL);
        $this->httpClient->addException(new RequestException('error', $request));

        $this->filesystem->expects($this->never())
            ->method('dumpFile');

        $this->expectException(DataDownloadErrorException::class);
        $this->downloaderService->getDataFile($data);
    }

    public function testGetDataFileWithDownloadIOException()
    {
        $data = $this->buildData();

        $this->nameGenerator->expects($this->exactly(2))
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(false);

        $this->configureExpectedGetRequest(self::DATA_URL, self::DATA_FILE_CONTENTS);

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->willThrowException(new IOException('io exception'))
            ->with(self::DATA_TEMP_FILENAME, self::DATA_FILE_CONTENTS);

        $this->expectException(DataDownloadErrorException::class);
        $this->downloaderService->getDataFile($data);
    }

    public function testGetDataFileWithDownloadNot200ResponseThrowsException()
    {
        $data = $this->buildData();

        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(false);

        $this->mockGetRequest(self::DATA_URL);
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(404);
        $this->httpClient->addResponse($response);

        $this->filesystem->expects($this->never())
            ->method('dumpFile');

        $this->expectException(DataDownloadErrorException::class);
        $this->downloaderService->getDataFile($data);
    }

    public function testGetDataFileWithNoExistingDownloadedFile()
    {
        $data = $this->buildData();

        $this->nameGenerator->expects($this->exactly(2))
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(false);

        $this->configureExpectedGetRequest(self::DATA_URL, self::DATA_FILE_CONTENTS);

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with(self::DATA_TEMP_FILENAME, self::DATA_FILE_CONTENTS);

        $file = $this->downloaderService->getDataFile($data);
        $this->assertInstanceOf(\SplFileInfo::class, $file);
    }

    public function testGetDataFileWithDownloadedFileSameHash()
    {
        $data = $this->buildData();
        $data->hash = $this->prepareTempDataFile();

        $this->nameGenerator->expects($this->once())
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(true);

        $this->filesystem->expects($this->never())
            ->method('dumpFile');

        $this->messageFactory->expects($this->never())
            ->method('createRequest');

        $file = $this->downloaderService->getDataFile($data);
        $this->assertInstanceOf(\SplFileInfo::class, $file);
    }

    public function testGetDataFileWithDownloadedFileDifferentHash()
    {
        $data = $this->buildData();
        $this->prepareTempDataFile();
        $data->hash = hash('sha512', self::DATA_TEMP_FILENAME);

        $this->nameGenerator->expects($this->exactly(2))
            ->method('buildDownloadDataFilename')
            ->with($data->uuid)
            ->willReturn(self::DATA_TEMP_FILENAME);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with(self::DATA_TEMP_FILENAME)
            ->willReturn(true);

        $this->configureExpectedGetRequest(self::DATA_URL, self::DATA_FILE_CONTENTS);

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with(self::DATA_TEMP_FILENAME, self::DATA_FILE_CONTENTS);

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

    private function prepareTempDataFile()
    {
        $contents = 'data-temp-file-uuid';
        file_put_contents(self::DATA_TEMP_FILENAME, $contents);

        return hash('sha512', $contents);
    }

    private function configureExpectedGetRequest(string $dataUrl, string $fileContents): void
    {
        $this->mockGetRequest($dataUrl);
        $response = $this->mockGetResponse();

        $resource = fopen('php://temp', 'w+');
        fwrite($resource, $fileContents);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn(new Stream($resource));
    }

    /**
     * @return RequestInterface|MockObject
     */
    private function mockGetRequest(string $dataUrl)
    {
        $request = $this->createMock(RequestInterface::class);
        $this->messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $dataUrl)
            ->willReturn($request);

        return $request;
    }

    /**
     * @throws \ReflectionException
     *
     * @return MockObject|ResponseInterface
     */
    private function mockGetResponse()
    {
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $this->httpClient->addResponse($response);

        return $response;
    }
}
