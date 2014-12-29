<?php

namespace KCore\CoreBundle\Services;

use JMS\Serializer\Exception\UnsupportedFormatException;
use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use KCore\CoreBundle\Libraries\SolrSearchHelper;
use Solarium\Client;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class DocumentService {

    /** @var  CoreService */
    protected $coreService;

    /** @var RecursiveValidator */
    protected $validatorService;

    /**
     * @param CoreService $coreService
     * @param RecursiveValidator $validatorService
     */
    function __construct(CoreService $coreService, RecursiveValidator $validatorService) {
        $this->coreService = $coreService;
        $this->validatorService = $validatorService;
    }

    /**
     * @param DocumentDescriptor $documentDescriptor
     * @param File $file
     * @return \Solarium\QueryType\Update\Result
     *
     * @todo: implement the "401 - Not Authorized" check and response
     * @todo: fix the UnsupportedFormatException and provide a better exception handling!
     */
    public function indexDocumentDescriptor(DocumentDescriptor $documentDescriptor, File $file = null) {

        $errors = $this->validatorService->validate($documentDescriptor);
        if ($errors->count() > 0) {
            throw new UnsupportedFormatException($errors);
        }

        $client = $this->getClientByDocumentVisibility($documentDescriptor->getVisibility());
        if (!$client) {
            throw new UnsupportedFormatException('No Index-Client to support your request!');
        }

        $doc = SolrDocumentDescriptor::buildFromEntity($documentDescriptor);

        // Index the DocumentDescriptor and parse the given file
        if ($file instanceof File && $file->isReadable()) {
            $extract = $client->createExtract();
            $extract->setFile($file->getRealPath());
            $extract->setFieldMappings(array('content' => SolrDocumentDescriptor::FIELD_DOC_CONTENTS));
            $extract->setUprefix('str_sm_doc_attributes_');
            $extract->setDocument($doc);
            $extract->setCommit(true);
            return $client->extract($extract);
        }
        else {
            $update = $client->createUpdate();
            $update->addDocument($doc);
            $update->addCommit();
            return $client->update($update);
        }
    }

    /**
     * @param $institutionId
     * @param $localDocumentId
     * @param bool $publicIndexOnly : performs only a Public search
     * @return DocumentDescriptor|null
     */
    public function getDocumentDescriptor($institutionId, $localDocumentId, $publicIndexOnly = true) {
        $docId = DocumentDescriptor::computeDocumentId($institutionId, $localDocumentId);

        $documentDescriptor = null;

        // If the requested document MAY be a private document, first look for it on the "Private" index
        if (!$publicIndexOnly && $institutionId == $this->coreService->getInstitutionId()) {
            $documentDescriptor = $this->getDocumentDescriptorFromClient($this->coreService->getPrivateSolrClient() , $docId);
        }

        if ($documentDescriptor == null) {
            $documentDescriptor = $this->getDocumentDescriptorFromClient($this->coreService->getPublicSolrClient() , $docId);
        }

        return $documentDescriptor;
    }


    /**
     * @param $id
     * @param bool $publicSearchOnly
     * @return DocumentDescriptor|null
     */
    public function getDocumentDescriptorById($id, $publicSearchOnly = true) {
        $ids = DocumentDescriptor::splitDocumentId($id);
        if ($ids == null) {
            return $ids;
        }
        return $this->getDocumentDescriptor($ids[0], $ids[1], $publicSearchOnly);
    }


    /**
     * @param Client $client
     * @param $id
     * @return DocumentDescriptor|null
     */
    private function getDocumentDescriptorFromClient(Client $client, $id) {
        $select = $client->createSelect();
        $select
            ->setDocumentClass('KCore\CoreBundle\Entity\SolrDocumentDescriptor')
            ->setStart(0)
            ->setRows(1)
            ->setQuery(SolrDocumentDescriptor::FIELD_DOC_ID .':"' . $id . '"');

        $filters = SolrSearchHelper::buildFilterQueries(array(
            'filter' => array(
                'field' => SolrDocumentDescriptor::FIELD_ENTITY_TYPE,
                'value' => DocumentDescriptor::ENTITY_TYPE,
            )
        ));
        $select->addFilterQueries($filters);

        $resultSet = $client->select($select);

        if ($resultSet->count() != 1) {
            return NULL;
        }
        else {
            /** @var SolrDocumentDescriptor $obj */
            $obj = $resultSet->getIterator()[0];
            return $obj->getDocumentDescriptor();
        }
    }


    /**
     * @param DocumentDescriptor $documentDescriptor
     * @return \Solarium\QueryType\Update\Result
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function deleteDocumentDescriptor(DocumentDescriptor $documentDescriptor) {
        $client = $this->getClientByDocumentVisibility($documentDescriptor->getVisibility());
        if (!$client) {
            throw new UnsupportedFormatException();
        }

        $update = $client->createUpdate();
        $update->addDeleteById($documentDescriptor->getEntityId());
        $update->addCommit();
        return $client->update($update);
    }

    /**
     * @param string $visibility
     * @return \Solarium\Client|null
     */
    private function getClientByDocumentVisibility($visibility) {
        if ($visibility == DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE) {
            $client = $this->coreService->getPrivateSolrClient();
        }
        elseif($visibility == DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC) {
            $client = $this->coreService->getPublicSolrClient();
        }
        else {
            return null;
        }
        return $client;
    }


    /**
     * @param $queryTerms
     * @param bool $publicSearch
     * @param array $facets
     * @param int $start
     * @param int $rows
     * @return \Solarium\QueryType\Select\Result\Result|null
     */
    public function searchDocumentDescriptor($queryTerms, $publicSearch = true, array $facets = array(), $start = 0, $rows = 10) {
        $visibility = $publicSearch ? DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC : DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE;
        $client = $this->getClientByDocumentVisibility($visibility);

        if(!$client) {
            return null;
        }

        $queryFilters = array(
            'doctype' => array(
                'field' => SolrDocumentDescriptor::FIELD_ENTITY_TYPE,
                'value' => DocumentDescriptor::ENTITY_TYPE
            )
        );

        $selectQuery = $client->createSelect();
        $selectQuery->addFilterQueries(SolrSearchHelper::buildFilterQueries($queryFilters));
        $edisMax = $selectQuery->getEDisMax();

        // Extra configuration: EDisMax fields
        $edisMax->setQueryFields(implode(' ', array(
            SolrDocumentDescriptor::FIELD_DOC_CONTENTS,
            SolrDocumentDescriptor::FIELD_DOC_TITLE_INDEXED . '^2.0',
            SolrDocumentDescriptor::FIELD_DOC_ABSTRACT_INDEXED . '^1.5',
        )));

        // Results configuration
        $selectQuery
            ->setStart($start)
            ->setRows($rows)
            ->setDocumentClass('KCore\CoreBundle\Entity\SolrDocumentDescriptor');

        $selectQuery->setOmitHeader(false);
        $selectQuery->setQuery($queryTerms);

        return $client->select($selectQuery);
    }

}