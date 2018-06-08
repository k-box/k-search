<?php

namespace App\Tests\Service;

use App\Entity\DataProcessingStatus;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Repository\DataProcessingStatusRepository;
use App\Service\DataProcessingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class DataProcessingServiceTest extends TestCase
{
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const REQUEST_ID = 'a1b2c3d4e5f6';

    /**
     * @var DataProcessingService
     */
    private $dataProcessingService;

    /**
     * @var DataProcessingStatusRepository|MockObject
     */
    private $repository;

    /**
     * @var MessageBusInterface|MockObject
     */
    private $messageBus;

    protected function setUp()
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->repository = $this->createMock(DataProcessingStatusRepository::class);
        $this->dataProcessingService = new DataProcessingService($this->repository, $this->messageBus);
    }

    public function testEnqueueData(): void
    {
        $this->repository->expects($this->once())
            ->method('createOrUpdate')
            ->with($this->callback(function (DataProcessingStatus $status) {
                $this->assertSame(self::DATA_UUID, $status->getDataUuid());
                $this->assertSame(self::REQUEST_ID, $status->getRequestId());
                $this->assertSame(DataStatus::STATUS_QUEUED_OK, $status->getStatus());
                $this->assertTrue(new \DateTime('-1 sec') < $status->getAddedAt());

                return true;
            }));
        $this->messageBus->expects($this->once())
            ->method('dispatch');

        $data = new Data();
        $data->requestId = self::REQUEST_ID;
        $data->uuid = self::DATA_UUID;
        $data->status = DataStatus::STATUS_QUEUED_OK;
        $data->updatedAt = new \DateTime();

        $this->dataProcessingService->addDataForProcessing($data);
    }
}
