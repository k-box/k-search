<?php

namespace App\Tests\Command;

use App\Command\DataIndexWorkerCommand;
use App\Exception\DataDownloadErrorException;
use App\Exception\InternalSearchException;
use App\Exception\SolrExtractionException;
use App\Model\Data\Data;
use App\Queue\Message\UUIDMessage;
use App\Service\DataDownloaderService;
use App\Service\DataService;
use App\Service\QueueService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DataProcessWorkerCommandTest extends KernelTestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';

    /** @var DataService|\PHPUnit_Framework_MockObject_MockObject */
    private $dataService;

    /** @var QueueService|\PHPUnit_Framework_MockObject_MockObject */
    private $queueService;

    /** @var DataDownloaderService|\PHPUnit_Framework_MockObject_MockObject */
    private $dataDownloaderService;

    /** @var Application */
    private $application;

    public function setUp()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $this->application = new Application($kernel);

        $this->queueService = $this->createMock(QueueService::class);
        $this->dataService = $this->createMock(DataService::class);
        $this->dataDownloaderService = $this->createMock(DataDownloaderService::class);
    }

    public function testItHandlesSolrInternalError()
    {
        $data = new Data();
        $data->uuid = self::DATA_UUID;
        $data->url = 'https://localhost/file.pdf';

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $fileInfo = $this->createMock(\SplFileInfo::class);
        $this->dataDownloaderService->expects($this->once())
            ->method('downloadDataContents')
            ->with($data)
            ->willReturn($fileInfo);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $fileInfo)
            ->willThrowException(new InternalSearchException('Error extracting!'));

        $this->dataDownloaderService->expects($this->once())
            ->method('removeDataContents')
            ->with($data);

        $this->dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $data) {
                $this->assertSame(self::DATA_UUID, $data->uuid);
                $this->assertSame(Data::STATUS_ERROR, $data->status);
                $this->assertSame('An error occurred while extracting text from the Data', $data->errorStatus);

                return true;
            }));

        $commandTester = $this->getDefaultCommandTester();

        $commandTester->execute(['--limit' => 1]);
    }

    public function testItHandlesSolrExtractionError()
    {
        $data = new Data();
        $data->uuid = self::DATA_UUID;
        $data->url = 'https://localhost/file.pdf';

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $fileInfo = $this->createMock(\SplFileInfo::class);
        $this->dataDownloaderService->expects($this->once())
            ->method('downloadDataContents')
            ->with($data)
            ->willReturn($fileInfo);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $fileInfo)
            ->willThrowException(new SolrExtractionException('Error extracting!'));

        $this->dataDownloaderService->expects($this->once())
            ->method('removeDataContents')
            ->with($data);

        $this->dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $data) {
                $this->assertSame(self::DATA_UUID, $data->uuid);
                $this->assertSame(Data::STATUS_ERROR, $data->status);
                $this->assertSame('An error occurred while extracting text from the Data', $data->errorStatus);

                return true;
            }));

        $commandTester = $this->getDefaultCommandTester();

        $commandTester->execute(['--limit' => 1]);
    }

    public function testItHandlesDownloadError()
    {
        $data = new Data();
        $data->uuid = self::DATA_UUID;
        $data->url = 'https://localhost/file.pdf';

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $this->dataDownloaderService->expects($this->once())
            ->method('downloadDataContents')
            ->with($data)
            ->willThrowException(new DataDownloadErrorException('Error downloading!'));

        $this->dataService->expects($this->never())
            ->method('addDataWithFileExtraction');

        $this->dataDownloaderService->expects($this->once())
            ->method('removeDataContents')
            ->with($data);

        $this->dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $data) {
                $this->assertSame(self::DATA_UUID, $data->uuid);
                $this->assertSame(Data::STATUS_ERROR, $data->status);
                $this->assertSame('Error downloading!', $data->errorStatus);

                return true;
            }));

        $commandTester = $this->getDefaultCommandTester();

        $commandTester->execute(['--limit' => 1]);
    }

    public function testItDownloadsAndAddsDataToIndex()
    {
        $data = new Data();
        $data->uuid = self::DATA_UUID;
        $data->url = 'https://localhost/file.pdf';

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $file = $this->createMock(\SplFileInfo::class);
        $this->dataDownloaderService->expects($this->once())
            ->method('downloadDataContents')
            ->with($data)
            ->willReturn($file);

        $this->dataService->expects($this->once())
            ->method('addDataWithFileExtraction')
            ->with($data, $file);

        $this->dataDownloaderService->expects($this->once())
            ->method('removeDataContents')
            ->with($data);
        $commandTester = $this->getDefaultCommandTester();

        $commandTester->execute([
            '--limit' => 1,
        ]);
    }

    private function getDefaultCommandTester(): CommandTester
    {
        $command = new DataIndexWorkerCommand(
            $this->queueService,
            $this->dataService,
            $this->dataDownloaderService,
            $this->createMock(LoggerInterface::class)
        );
        $this->application->add($command);

        $command = $this->application->find('ksearch:data:index-worker');

        $message = new UUIDMessage(self::DATA_UUID);
        $this->queueService->expects($this->once())
            ->method('dequeMessage')
            ->with(QueueService::DATA_PROCESS_QUEUE)
            ->willReturn($message);

        return new CommandTester($command);
    }
}
