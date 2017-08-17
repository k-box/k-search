<?php

namespace App\Service;

use App\Entity\SolrEntityData;
use App\Helper\DataHelper;
use App\Manager\DataManager;
use App\Model\Data\Data;
use DateTimeZone;

class DataService
{
    /**
     * @var SolrService
     */
    private $solrService;
    /**
     * @var DataHelper
     */
    private $dataHelper;
    /**
     * @var DataManager
     */
    private $manager;

    public function __construct(SolrService $solrService, DataHelper $dataHelper, DataManager $manager)
    {
        $this->solrService = $solrService;
        $this->dataHelper = $dataHelper;
        $this->manager = $manager;
    }

    /**
     * Delete a data entity from the index, given its UUID.
     *
     * @param string $uuid The Data UUID
     *
     * @return bool
     */
    public function deleteData(string $uuid)
    {
        return $this->solrService->delete(SolrEntityData::getEntityType(), $uuid);
    }

    /**
     * Retrieves the Data from the index, given its UUID.
     *
     * @param string $uuid the data UUID
     *
     * @return Data
     */
    public function getData(string $uuid): Data
    {
        /** @var SolrEntityData $solrEntityData */
        $solrEntityData = $this->solrService->get(SolrEntityData::getEntityType(), $uuid, SolrEntityData::class);

        return $solrEntityData->buildModel();
    }

    public function addData(Data $data, ?string $textualContents): bool
    {
        if (!$data->properties->updated_at) {
            $data->properties->updated_at = new \DateTime();
            $data->properties->updated_at->setTimezone(new DateTimeZone('UTC'));
        }

        if ($textualContents) {
            $dataEntity = SolrEntityData::buildFromModel($data, $textualContents);
        } else {
            if (!$this->dataHelper->isIndexable($data)) {
                $dataEntity = SolrEntityData::buildFromModel($data);
            } else {
                $this->manager->handleIndexableDataWithoutTextualContent($data);

                return false;
            }
        }

        $this->solrService->add($dataEntity);

        return true;
    }
}
