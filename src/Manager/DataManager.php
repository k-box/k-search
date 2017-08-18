<?php

namespace App\Manager;

use App\Model\Data\Data;
use App\Service\QueueService;

class DataManager
{

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * DataManager constructor.
     * @param QueueService $service
     */
    public function __construct(QueueService $service)
    {
        $this->queueService = $service;
    }

    public function saveDataToBeProcessed(Data $data): bool
    {
        $this->queueService->enqueueUUID($data);
        return true;
    }
}
