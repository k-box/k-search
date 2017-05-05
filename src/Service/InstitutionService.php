<?php

namespace App\Service;

use App\Entity\InstitutionDescriptor;
use App\Entity\SolrInstitutionDescriptor;
use App\Libraries\SolrSearchHelper;
use Solarium\QueryType\Select\Query\FilterQuery;

class InstitutionService
{
    /**
     * @var CoreService
     */
    protected $coreService;

    public function __construct(CoreService $coreService)
    {
        $this->coreService = $coreService;
    }

    /**
     * Index the given list of InstitutionDescriptor.
     *
     * @param InstitutionDescriptor[] $institutionDescriptors
     *
     * @return \Solarium\QueryType\Update\Result
     */
    public function indexInstitutionDescriptors(array $institutionDescriptors)
    {
        $client = $this->coreService->getPublicSolrClient();
        $update = $client->createUpdate();
        foreach ($institutionDescriptors as $institutionDescriptor) {
            $doc = SolrInstitutionDescriptor::buildFromEntity($institutionDescriptor);
            $update->addDocument($doc);
        }

        $update->addCommit();
        $result = $client->update($update);

        return $result;
    }

    /**
     * Index the given InstitutionDescriptor.
     *
     * @param InstitutionDescriptor $institutionDescriptor
     *
     * @return \Solarium\QueryType\Update\Result
     */
    public function indexInstitutionDescriptor(InstitutionDescriptor $institutionDescriptor)
    {
        return $this->indexInstitutionDescriptors([$institutionDescriptor]);
    }

    /**
     * Returns the complete list of indexed Institutions.
     *
     * @param int $numResults  The number or results to return
     * @param int $startResult The first item's index to return, used for pagination. 0-based"
     *
     * @return InstitutionDescriptor[]
     */
    public function getAllInstitutionDescriptors($numResults = 100, $startResult = 0)
    {
        $client = $this->coreService->getPublicSolrClient();

        $select = $client->createSelect();
        $select->setDocumentClass('App\Entity\SolrInstitutionDescriptor');
        $filters = $this->buildEntityTypeFilterQuery();
        $select->addFilterQueries($filters);

        $select->setRows($numResults);
        $select->setStart($startResult);

        // Sorting by InstitutionID
        $select->addSort(SolrInstitutionDescriptor::FIELD_INST_ID, 'ASC');

        $resultSet = $client->select($select);

        $institutions = [];
        /** @var SolrInstitutionDescriptor $solrInstitution */
        foreach ($resultSet->getIterator() as $solrInstitution) {
            $institutions[] = $solrInstitution->getInstitutionDescriptor();
        }

        return $institutions;
    }

    /**
     * Retrieves from the index the InstitutionDescriptor given the Institution's ID.
     *
     * @param $id
     *
     * @return InstitutionDescriptor|null
     */
    public function getInstitutionDescriptor($id)
    {
        $client = $this->coreService->getPublicSolrClient();
        $select = $client->createSelect();
        $select
            ->setDocumentClass('App\Entity\SolrInstitutionDescriptor')
            ->setStart(0)
            ->setRows(1)
            ->setQuery(SolrInstitutionDescriptor::FIELD_INST_ID.':"'.$id.'"');

        $filters = $this->buildEntityTypeFilterQuery();
        $select->addFilterQueries($filters);

        $resultSet = $client->select($select);

        if ($resultSet->count() !== 1) {
            return null;
        }

        /** @var SolrInstitutionDescriptor $obj */
        $obj = $resultSet->getIterator()[0];

        return $obj->getInstitutionDescriptor();
    }

    /**
     * @param InstitutionDescriptor $institutionDescriptor
     *
     * @return \Solarium\QueryType\Update\Result
     */
    public function deleteInstitutionDescriptor(InstitutionDescriptor $institutionDescriptor)
    {
        $client = $this->coreService->getPublicSolrClient();
        $update = $client->createUpdate();
        $update->addDeleteById($institutionDescriptor->getEntityId());
        $update->addCommit();

        return $client->update($update);
    }

    /**
     * @return \Solarium\QueryType\Update\Result
     */
    public function deleteAllInstitutions()
    {
        $client = $this->coreService->getPublicSolrClient();
        $update = $client->createUpdate();
        $filters = $this->buildEntityTypeFilterQuery();
        $filter = current($filters)->getQuery();
        $update->addDeleteQuery($filter);
        $update->addCommit();

        return $client->update($update);
    }

    /**
     * Returns if the InstitutionAPI is enabled or not, depending on the KSearch type.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->coreService->getCoreType() === CoreService::TYPE_PUBLIC;
    }

    /**
     * @return FilterQuery[]
     */
    protected function buildEntityTypeFilterQuery()
    {
        return SolrSearchHelper::buildFilterQueries([
            'field' => SolrInstitutionDescriptor::FIELD_ENTITY_TYPE,
            'value' => InstitutionDescriptor::ENTITY_TYPE,
        ]);
    }
}
