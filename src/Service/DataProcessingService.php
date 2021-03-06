<?php

namespace App\Service;

use App\Entity\DataProcessingStatus;
use App\Exception\ProcessingOverloadedException;
use App\Exception\ProcessingStatusNotFoundException;
use App\Helper\DateHelper;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Queue\Message\DataProcessingMessage;
use App\Repository\DataProcessingStatusRepository;
use Doctrine\DBAL\Exception\RetryableException;
use Symfony\Component\Messenger\MessageBusInterface;

class DataProcessingService
{
    public const DATA_QUEUED_TOPIC = 'data-queued';

    /**
     * @var DataProcessingStatusRepository
     */
    private $processingRepository;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(DataProcessingStatusRepository $processingRepository, MessageBusInterface $messageBus)
    {
        $this->processingRepository = $processingRepository;
        $this->messageBus = $messageBus;
    }

    public function addDataForProcessing(Data $data): DataProcessingStatus
    {
        $status = $this->createRecordForData($data);
        try {
            $this->processingRepository->createOrUpdate($status);
        } catch (RetryableException $e) {
            $e = new \Exception();
            throw new ProcessingOverloadedException(
                sprintf('Too many request for uuid=%s. Please throttle your requests or retry later', $data->uuid),
                429,
                $e
            );
        }

        switch ($data->status) {
            case DataStatus::STATUS_QUEUED_OK:
                $this->messageBus->dispatch(DataProcessingMessage::fromStatus($status));
                break;
            default:
                // Do nothing
        }

        return $status;
    }

    /**
     * @throws ProcessingStatusNotFoundException
     */
    public function getProcessingStatus(string $uuid, string $requestId = null, string $status = null): DataProcessingStatus
    {
        $criteria = ['id' => $uuid];
        if ($status) {
            $criteria['status'] = $status;
        }
        if ($requestId) {
            $criteria['requestId'] = $requestId;
        }

        if ($processingStatus = $this->processingRepository->findOneBy($criteria)) {
            return $processingStatus;
        }

        throw new ProcessingStatusNotFoundException(sprintf('Processing status not found for resource data:%s', $uuid));
    }

    public function deleteProcessingStatus(string $uuid): void
    {
        $this->processingRepository->delete($uuid);
    }

    /**
     * Delete the given status, only if it matches the given criteria.
     *
     * Deletion is done in an atomic way with locking, thus trying to avoid deleting the processing status added
     * by another requestId.
     */
    public function deleteProcessingStatusByCriteria(string $uuid, string $requestId, string $status): void
    {
        $this->processingRepository->deleteOneByCriteria([
            'id' => $uuid,
            'requestId' => $requestId,
            'status' => $status,
        ]);
    }

    /**
     * @throws ProcessingStatusNotFoundException
     */
    public function updateProcessingStatusStatus(
        string $uuid,
        string $requestId,
        string $status,
        string $newStatus,
        string $message
    ): DataProcessingStatus {
        $criteria = [
            'id' => $uuid,
            'requestId' => $requestId,
            'status' => $status,
        ];
        $statusEntity = $this->processingRepository->updateStatusByCriteria($criteria, $newStatus, $message);

        if (!$statusEntity) {
            throw new ProcessingStatusNotFoundException('Processing status not found!');
        }

        return $statusEntity;
    }

    private function createRecordForData(Data $data): DataProcessingStatus
    {
        return (new DataProcessingStatus())
            ->setAddedAt(DateHelper::createUtcDate())
            ->setStatus($data->status)
            ->setRequestId($data->requestId)
            ->setDataUuid($data->uuid)
            ->setData($data);
    }
}
