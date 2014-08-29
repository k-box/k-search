<?php

namespace KCore\CoreBundle\Services;


use KCore\CoreBundle\Entity\InstitutionDescriptor;
use KCore\CoreBundle\Entity\SolrInstitutionDescriptor;
use KCore\CoreBundle\Libraries\SolrSearchHelper;

class InstitutionService {

    protected $client;
    public function __construct(CoreService $coreService) {

        $this->client = $coreService->getPublicSolrClient();
    }


    /**
     * @param InstitutionDescriptor $institutionDescriptor
     * @return \Solarium\QueryType\Update\Result
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function indexInstitutionDescriptor(InstitutionDescriptor $institutionDescriptor) {
        $update = $this->client->createUpdate();
        $doc = SolrInstitutionDescriptor::buildFromEntity($institutionDescriptor);

        $update->addDocument($doc);
        $update->addCommit();
        $result = $this->client->update($update);
        return $result;
    }


    /**
     * Retrives from the index the InstitutionDescriptor given the
     * @param $id
     * @return InstitutionDescriptor|null
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function getInstitutionDescriptor($id) {
        $select = $this->client->createSelect();
        $select
            ->setDocumentClass('KCore\CoreBundle\Entity\SolrInstitutionDescriptor')
            ->setStart(0)
            ->setRows(1)
            ->setQuery(SolrInstitutionDescriptor::FIELD_INST_ID .':"' . $id . '"');

        $filters = SolrSearchHelper::buildFilterQueries(array(
            'filter' => array(
                'field' => SolrInstitutionDescriptor::FIELD_ENTITY_TYPE,
                'value' => InstitutionDescriptor::ENTITY_TYPE,
            )
        ));
        $select->addFilterQueries($filters);

        $resultSet = $this->client->select($select);

        if ($resultSet->count() != 1) {
            return NULL;
        }
        else {
            /** @var SolrInstitutionDescriptor $obj */
            $obj = $resultSet->getIterator()[0];
            return $obj->getInstitutionDescriptor();
        }
    }

    /**
     * @param InstitutionDescriptor $institutionDescriptor
     * @return \Solarium\QueryType\Update\Result
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function deleteInstitutionDescriptor(InstitutionDescriptor $institutionDescriptor) {
        $update = $this->client->createUpdate();
        $update->addDeleteById($institutionDescriptor->getEntityId());
        $update->addCommit();
        return $this->client->update($update);
    }
} 