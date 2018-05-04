<?php

namespace App\Service;

use App\Entity\SolrEntityData;
use App\Exception\ProcessingStatusNotFoundException;
use App\Exception\SolrEntityNotFoundException;
use App\Helper\DateHelper;
use App\Model\Data\DataStatus;
use Solarium\QueryType\Select\Result\Document;

class DataStatusService
{
    /**
     * @var SolrService
     */
    private $solrService;

    /**
     * @var DataProcessingService
     */
    private $dataQueueService;

    public function __construct(SolrService $solrService, DataProcessingService $dataQueueService)
    {
        $this->solrService = $solrService;
        $this->dataQueueService = $dataQueueService;
    }

    /**
     * Retrieves the Data status, given the requested stage.
     *
     * @throws SolrEntityNotFoundException
     * @throws ProcessingStatusNotFoundException
     *
     * @return DataStatus
     */
    public function getDataStatus(string $type, string $uuid): DataStatus
    {
        // Set default if not set
        $type = $type ?? DataStatus::TYPE_DATA;

        switch ($type) {
            case DataStatus::TYPE_PROCESSING:
                $status = $this->getDataStatusFromProcessing($uuid);
                break;
            case DataStatus::TYPE_DATA:
                $status = $this->getDataStatusFromIndex($uuid);
                break;
            default:
                throw new \InvalidArgumentException('Wrong data type requested');
        }

        return $status;
    }

    /**
     * Checks if the data available in the index is newer than the given DateTime.
     */
    public function isDataNewer(string $uuid, \DateTimeInterface $dateTime): bool
    {
        try {
            $document = $this->getSingleDocumentForData($uuid, [SolrEntityData::FIELD_UPDATED_AT]);
            $updatedAt = $document->getFields()[SolrEntityData::FIELD_UPDATED_AT] ?? null;

            return $updatedAt ? $dateTime < DateHelper::createUtcDate($updatedAt) : false;
        } catch (SolrEntityNotFoundException $e) {
            // If no data is in the index, the data is not newer
            return false;
        }
    }

    /**
     * @param string $uuid
     *
     * @throws SolrEntityNotFoundException
     *
     * @return DataStatus
     */
    private function getDataStatusFromIndex(string $uuid): DataStatus
    {
        $fields = [
            SolrEntityData::FIELD_UPDATED_AT,
            SolrEntityData::FIELD_REQUEST_ID,
            SolrEntityData::FIELD_STATUS,
            SolrEntityData::FIELD_ERROR_STATUS,
        ];
        $document = $this->getSingleDocumentForData($uuid, $fields);

        // Building the required SolrEntity object from the result document
        $solrEntityData = new SolrEntityData($uuid, $document);
        $data = $solrEntityData->buildModel();

        return DataStatus::fromData($data);
    }

    /**
     * @throws SolrEntityNotFoundException
     */
    private function getSingleDocumentForData(string $uuid, array  $fields): Document
    {
        $entityType = SolrEntityData::getEntityType();

        $filterQueries = [
            $this->solrService->buildFilterQuery(SolrEntityData::FIELD_ENTITY_ID, $uuid, 'id'),
        ];

        $resultSet = $this->solrService->getByFilter($entityType, SolrEntityData::class, $filterQueries, 1, 0, $fields);

        if (1 !== $resultSet->getNumFound()) {
            throw new SolrEntityNotFoundException(sprintf('Resource %s::%s not found!', $entityType, $uuid));
        }

        /** @var Document[] $documents */
        $documents = $resultSet->getDocuments();

        return $documents[0];
    }

    /**
     * @throws ProcessingStatusNotFoundException
     */
    private function getDataStatusFromProcessing(string $uuid): DataStatus
    {
        $processingStatus = $this->dataQueueService->getProcessingStatus($uuid);

        return DataStatus::fromProcessingStatus($processingStatus);
    }
}
