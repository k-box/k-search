<?php
namespace App\Tests\Command;

use App\Command\DataProcessWorkerCommand;
use App\Service\DataService;
use App\Service\QueueService;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DataProcessWorkerCommandTest extends KernelTestCase
{
    /** @var  CommandTester */
    private $commandTester;

    private $dataService;
    private $httpClient;
    private $messageFactory;
    private $tempFolder;
    private $queueService;

    public function setUp()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $application = new Application($kernel);

        $this->queueService = $this->createMock(QueueService::class);
        $this->dataService = $this->createMock(DataService::class);
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->tempFolder = __DIR__.'/../var/tests';

        $command = new DataProcessWorkerCommand($this->queueService, $this->dataService, $this->httpClient, $this->messageFactory, $this->tempFolder);
        $application->add($command);

        $command = $application->find('ksearch:data-process:worker');
        $this->commandTester = new CommandTester($command);
    }

    public function testItDownloadsAndAddsDataToIndex() {
        $this->commandTester->execute([
            '--loops' => 1
        ]);

        $this->assertFalse(true);
    }
}