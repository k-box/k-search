<?php

namespace KCore\CoreBundle\Services;

use KCore\CoreBundle\Entity\InstitutionDescriptor;
use KCore\CoreBundle\Entity\SolrInstitutionDescriptor;
use KCore\CoreBundle\Libraries\SolrSearchHelper;

class InstitutionService
{
    /**
     * @var \Solarium\Client
     */
    protected $client;

    public function __construct(CoreService $coreService)
    {
        $this->client = $coreService->getPublicSolrClient();
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
        $update = $this->client->createUpdate();
        foreach ($institutionDescriptors as $institutionDescriptor) {
            $doc = SolrInstitutionDescriptor::buildFromEntity($institutionDescriptor);
            $update->addDocument($doc);
        }

        $update->addCommit();
        $result = $this->client->update($update);

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
     * @return InstitutionDescriptor[]
     */
    public function getAllInstitutionDescriptors($numResults = 100, $startResult = 0)
    {
        $select = $this->client->createSelect();
        $select->setDocumentClass('KCore\CoreBundle\Entity\SolrInstitutionDescriptor');
        $filters = $this->buildEntityTypeFilterQuery();
        $select->addFilterQueries($filters);

        $select->setRows($numResults);
        $select->setStart($startResult);

        // Sorting by InstitutionID
        $select->addSort(SolrInstitutionDescriptor::FIELD_INST_ID, 'ASC');

        $resultSet = $this->client->select($select);

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
     * @return InstitutionDescriptor|void
     */
    public function getInstitutionDescriptor($id)
    {
        $select = $this->client->createSelect();
        $select
            ->setDocumentClass('KCore\CoreBundle\Entity\SolrInstitutionDescriptor')
            ->setStart(0)
            ->setRows(1)
            ->setQuery(SolrInstitutionDescriptor::FIELD_INST_ID.':"'.$id.'"');

        $filters = $this->buildEntityTypeFilterQuery();
        $select->addFilterQueries($filters);

        $resultSet = $this->client->select($select);

        if ($resultSet->count() != 1) {
            return;
        } else {
            /** @var SolrInstitutionDescriptor $obj */
            $obj = $resultSet->getIterator()[0];

            return $obj->getInstitutionDescriptor();
        }
    }

    /**
     * @param InstitutionDescriptor $institutionDescriptor
     *
     * @return \Solarium\QueryType\Update\Result
     */
    public function deleteInstitutionDescriptor(InstitutionDescriptor $institutionDescriptor)
    {
        $update = $this->client->createUpdate();
        $update->addDeleteById($institutionDescriptor->getEntityId());
        $update->addCommit();

        return $this->client->update($update);
    }

    /**
     * @return \Solarium\QueryType\Update\Result
     */
    public function deleteAllInstitutions()
    {
        $update = $this->client->createUpdate();
        $filters = $this->buildEntityTypeFilterQuery();
        $filter = current($filters)->getQuery();
        $update->addDeleteQuery($filter);
        $update->addCommit();

        return $this->client->update($update);
    }

    /**
     * @return \Solarium\QueryType\Select\Query\FilterQuery[]
     */
    protected function buildEntityTypeFilterQuery()
    {
        return SolrSearchHelper::buildFilterQueries([
            'field' => SolrInstitutionDescriptor::FIELD_ENTITY_TYPE,
            'value' => InstitutionDescriptor::ENTITY_TYPE,
        ]);
    }
}
