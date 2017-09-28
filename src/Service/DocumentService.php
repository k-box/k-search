<?php

namespace App\Service;

use App\Entity\DocumentDescriptor;
use App\Entity\SolrDocumentDescriptor;
use App\Libraries\SolrSearchHelper;
use App\Search\Facets\DocumentGroupsFacet;
use App\Search\Facets\DocumentProjectIdFacet;
use App\Search\Facets\DocumentTypeFacet;
use App\Search\Facets\FacetInterface;
use App\Search\Facets\InstitutionIdFacet;
use App\Search\Facets\LanguageFacet;
use App\Search\Facets\LocationsStringFacet;
use App\Search\Filters\DocumentGroupsFilter;
use App\Search\Filters\DocumentHashFilter;
use App\Search\Filters\DocumentIdFilter;
use App\Search\Filters\DocumentProjectIdFilter;
use App\Search\Filters\DocumentTypeFilter;
use App\Search\Filters\EntityTypeFilter;
use App\Search\Filters\FilterInterface;
use App\Search\Filters\InstitutionIdFilter;
use App\Search\Filters\LanguageFilter;
use App\Search\Filters\LocalDocumentIdFilter;
use App\Search\Filters\LocationsStringFilter;
use Solarium\Client;
use Solarium\Exception\HttpException;
use Solarium\QueryType\Select\Query\Component\Facet\Field;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Validator\RecursiveValidator;

/**
 * Class DocumentService.
 */
class DocumentService
{
    /** @var CoreService */
    protected $coreService;

    /** @var LocationExtractorService */
    protected $locationExtractorService;

    /** @var TextExtractorService */
    protected $textExtractorService;

    /** @var RecursiveValidator */
    protected $validatorService;

    protected $searchAllowedFilters;
    protected $searchAllowedFacets;

    /** @var string */
    protected $hashAlgorithm;

    /**
     * @param CoreService        $coreService      The Core service
     * @param RecursiveValidator $validatorService A Validator Service
     * @param string             $hashAlgorithm    The Hashing algorithm used to compute the Descriptor's hash
     */
    public function __construct(CoreService $coreService,
        RecursiveValidator $validatorService,
        $hashAlgorithm
    ) {
        $this->coreService = $coreService;
        $this->validatorService = $validatorService;
        $this->hashAlgorithm = $hashAlgorithm;

        $this->searchAllowedFilters = [];
        $this->searchAllowedFilters['language'] = LanguageFilter::class;
        $this->searchAllowedFilters['documentType'] = DocumentTypeFilter::class;
        $this->searchAllowedFilters['institutionId'] = InstitutionIdFilter::class;
        $this->searchAllowedFilters['documentGroups'] = DocumentGroupsFilter::class;
        $this->searchAllowedFilters['documentId'] = DocumentIdFilter::class;
        $this->searchAllowedFilters['localDocumentId'] = LocalDocumentIdFilter::class;
        $this->searchAllowedFilters['locationsString'] = LocationsStringFilter::class;
        $this->searchAllowedFilters['documentHash'] = DocumentHashFilter::class;
        $this->searchAllowedFilters['projectId'] = DocumentProjectIdFilter::class;

        $this->searchAllowedFacets = [];
        $this->searchAllowedFacets['language'] = LanguageFacet::class;
        $this->searchAllowedFacets['documentType'] = DocumentTypeFacet::class;
        $this->searchAllowedFacets['institutionId'] = InstitutionIdFacet::class;
        $this->searchAllowedFacets['documentGroups'] = DocumentGroupsFacet::class;
        $this->searchAllowedFacets['locationsString'] = LocationsStringFacet::class;
        $this->searchAllowedFacets['projectId'] = DocumentProjectIdFacet::class;
    }

    /**
     * @param LocationExtractorService $locationExtractorService
     */
    public function setLocationExtractorService(LocationExtractorService $locationExtractorService)
    {
        $this->locationExtractorService = $locationExtractorService;
    }

    /**
     * @return LocationExtractorService|null
     */
    public function getLocationExtractorService()
    {
        return $this->locationExtractorService;
    }

    /**
     * @return TextExtractorService|null
     */
    public function getTextExtractorService()
    {
        return $this->textExtractorService;
    }

    /**
     * @param TextExtractorService $textExtractorService
     */
    public function setTextExtractorService(TextExtractorService $textExtractorService)
    {
        $this->textExtractorService = $textExtractorService;
    }

    /**
     * @param $facetKey
     *
     * @return FacetInterface $class
     */
    public function createFacet($facetKey)
    {
        if (array_key_exists($facetKey, $this->searchAllowedFacets)) {
            $className = $this->searchAllowedFacets[$facetKey];

            return new $className(['key' => $facetKey]);
        }

        return null;
    }

    /**
     * @param $filterKey
     *
     * @return FilterInterface $class
     */
    public function createFilter($filterKey)
    {
        if (array_key_exists($filterKey, $this->searchAllowedFilters)) {
            $className = $this->searchAllowedFilters[$filterKey];

            return new $className(['key' => $filterKey]);
        }

        return null;
    }

    /**
     * @param DocumentDescriptor $documentDescriptor
     * @param File               $file
     *
     * @throws \Exception
     * @throws \Exception|\InvalidArgumentException
     *
     * @return \Solarium\QueryType\Update\Result
     * @todo: fix the UnsupportedFormatException and provide a better exception handling!
     */
    public function indexDocumentDescriptor(DocumentDescriptor $documentDescriptor, File $file = null)
    {
        $errors = $this->validatorService->validate($documentDescriptor);
        if ($errors->count() > 0) {
            throw new \InvalidArgumentException($errors);
        }

        $client = $this->getClientByDocumentVisibility($documentDescriptor->getVisibility());

        try {
            $extractLocationsFromDocumentDescriptor = 0 === count($documentDescriptor->getLocations()) &&
                0 === count($documentDescriptor->getLocationsString());

            // parse the given file
            if ($file instanceof File && $file->isReadable()) {
                if (!$this->validateDocumentDescriptorHashFromFile($documentDescriptor->getHash(), $file)) {
                    throw new \InvalidArgumentException('The given HASH does not match the hash computed on the posted content');
                }

                //perform locations extractions?
                if ($extractLocationsFromDocumentDescriptor) {
                    // Extract the File contents
                    $contents = $this->getTextExtractorService()->extractText($file, $documentDescriptor->isPublicDocument());

                    //extract and add the locations from content to the documentDescriptor
                    $this->extractAndAddLocationsToDocumentDescriptor($documentDescriptor, $contents);
                }

                // index the document
                $doc = SolrDocumentDescriptor::buildFromEntity($documentDescriptor);
                $extract = $client->createExtract();
                $extract->setFile($file->getRealPath());
                $extract->setFieldMappings(['content' => SolrDocumentDescriptor::FIELD_DOC_CONTENTS]);
                $extract->setUprefix('str_sm_doc_attributes_');
                $extract->setDocument($doc);
                $extract->setCommit(true);
                $result = $client->extract($extract);

                return $result;
            }
            if (!$this->validateDocumentDescriptorHashFromContents(
                    $documentDescriptor->getHash(),
                    $documentDescriptor->getContents()
                )) {
                throw new \InvalidArgumentException('The HASH does not match the hash computed on the received content');
            }

            //perform locations extractions?
            if ($extractLocationsFromDocumentDescriptor) {
                $this->extractAndAddLocationsToDocumentDescriptor($documentDescriptor, $documentDescriptor->getContents());
            }

            // index the document
            $doc = SolrDocumentDescriptor::buildFromEntity($documentDescriptor);
            $update = $client->createUpdate();
            $update->addDocument($doc);
            $update->addCommit();

            return $client->update($update);
        } catch (HttpException $e) {
            if (false !== strpos($e->getMessage(), '.PDFParser')) {
                throw new \Exception('PDF Parsing Exception', 500, $e);
            }
            throw $e;
        }
    }

    /**
     * Validate if the given hash correctly matches the given contents.
     *
     * @param string $hash     The document Descriptor's hash
     * @param string $contents The contents
     *
     * @return bool
     */
    public function validateDocumentDescriptorHashFromContents($hash, $contents)
    {
        // Check if the sent hash corresponds to the given contents
        return $hash === hash($this->hashAlgorithm, $contents);
    }

    /**
     * Validate if the given hash correctly matches the given file's contents.
     *
     * @param string $hash The Document descriptor's hash
     * @param File   $file
     *
     * @return bool
     */
    public function validateDocumentDescriptorHashFromFile($hash, File $file)
    {
        // Check if the sent hash corresponds to the given contents
        return $hash === hash_file($this->hashAlgorithm, $file->getPathname());
    }

    /**
     * @param $institutionId
     * @param $localDocumentId
     * @param string $visibility {@choice DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE, DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC}
     *
     * @throws WrongCoreException
     *
     * @return DocumentDescriptor|null
     */
    public function getDocumentDescriptor($institutionId, $localDocumentId, $visibility = DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC)
    {
        $documentId = DocumentDescriptor::computeDocumentId($institutionId, $localDocumentId);

        $client = $this->getClientByDocumentVisibility($visibility);

        if (!$client) {
            return null;
        }

        return $this->getDocumentDescriptorFromClient($client, $documentId);
    }

    /**
     * @param $documentId
     * @param string $visibility {@choice DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE, DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC}
     *
     * @throws WrongCoreException
     *
     * @return DocumentDescriptor|null
     */
    public function getDocumentDescriptorById($documentId, $visibility = DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC)
    {
        $ids = DocumentDescriptor::splitDocumentId($documentId);
        if (null === $ids) {
            return $ids;
        }

        return $this->getDocumentDescriptor($ids[0], $ids[1], $visibility);
    }

    /**
     * @param DocumentDescriptor $documentDescriptor
     *
     * @return \Solarium\QueryType\Update\Result
     */
    public function deleteDocumentDescriptor(DocumentDescriptor $documentDescriptor)
    {
        $client = $this->getClientByDocumentVisibility($documentDescriptor->getVisibility());
        $update = $client->createUpdate();
        $update->addDeleteById($documentDescriptor->getEntityId());
        $update->addCommit();

        return $client->update($update);
    }

    /**
     * @param string $visibility {@choice DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE, DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC}
     *
     * @return \Solarium\QueryType\Update\Result
     */
    public function deleteAllDocumentDescriptors($visibility = null)
    {
        $client = null;

        if ($visibility) {
            $client = $this->getClientByDocumentVisibility($visibility);
        } else {
            $type = $this->coreService->getCoreType();
            $client = $this->coreService->getCoreClientByType($type);
        }

        $update = $client->createUpdate();
        $update->addDeleteQuery(SolrDocumentDescriptor::FIELD_ENTITY_TYPE.':'.DocumentDescriptor::ENTITY_TYPE);
        $update->addCommit();

        return $client->update($update);
    }

    /**
     * @param                          $queryTerms
     * @param string                   $visibility {@choice DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE, DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC}
     * @param FilterInterface[]        $filters
     * @param FacetInterface[]|Field[] $facets
     * @param int                      $start
     * @param int                      $rows
     *
     * @return Result|null
     */
    public function searchDocumentDescriptor($queryTerms, $visibility = DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC, $filters = [], $facets = [], $start = 0, $rows = 10)
    {
        $client = $this->getClientByDocumentVisibility($visibility);
        if (!$client) {
            return null;
        }

        $selectQuery = $client->createSelect();

        // Document entity filter
        $entityFilter = new EntityTypeFilter(['key' => 'entitytype']);
        $entityFilter->setDefaultQueryWithValue(DocumentDescriptor::ENTITY_TYPE);
        $filters[] = $entityFilter;

        // EDisMax fields
        $edisMax = $selectQuery->getEDisMax();
        $edisMax->setQueryFields(implode(' ', [
            SolrDocumentDescriptor::FIELD_DOC_CONTENTS,
            SolrDocumentDescriptor::FIELD_DOC_TITLE_ALIASES_INDEXED.'^0.8',
            SolrDocumentDescriptor::FIELD_DOC_TITLE_INDEXED.'^2.0',
            SolrDocumentDescriptor::FIELD_DOC_ABSTRACT_INDEXED.'^1.5',
        ]));

        // Faceting
        if (count($facets)) {
            foreach ($facets as $facet) {
                $facet->handleEnabledFilters($filters);
            }

            $facetSet = $selectQuery->getFacetSet();
            foreach ($facets as $facet) {
                $facetSet->addFacet($facet);
            }
        }

        //Filtering
        $selectQuery->addFilterQueries($filters);

        // Results configuration
        $selectQuery
            ->setStart($start)
            ->setRows($rows)
            ->setDocumentClass(SolrDocumentDescriptor::class);
        $selectQuery->setOmitHeader(false);

        $selectQuery->setQuery($queryTerms);

        return $client->execute($selectQuery);
    }

    /**
     * @return array
     */
    public function getSearchAllowedFacets()
    {
        return $this->searchAllowedFacets;
    }

    /**
     * @return array
     */
    public function getSearchAllowedFilters()
    {
        return $this->searchAllowedFilters;
    }

    /**
     * @param DocumentDescriptor $documentDescriptor
     * @param string             $contents
     *
     * @return bool
     */
    public function extractAndAddLocationsToDocumentDescriptor(DocumentDescriptor &$documentDescriptor, $contents)
    {
        if (empty($contents) || !isset($this->locationExtractorService)) {
            return true;
        }

        try {
            $locations = $this->locationExtractorService->extractGeoJSONFeatureFromText($contents);
            if (empty($locations)) {
                return false;
            }
            foreach ($locations as $location) {
                //avoid duplicates
                $currentLocationsStrings = $documentDescriptor->getLocationsString();
                if (!is_array($currentLocationsStrings) || !in_array($location->getProperty('name'), $currentLocationsStrings, true)) {
                    $documentDescriptor->addLocation($location);
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the currently enabled visibility.
     *
     * @return string
     */
    public function getEnabledCoreVisibility()
    {
        return $this->coreService->getCoreType();
    }

    /**
     * @param Client $client
     * @param $documentId
     *
     * @return DocumentDescriptor|null
     */
    private function getDocumentDescriptorFromClient(Client $client, $documentId)
    {
        $select = $client->createSelect();
        $select
            ->setDocumentClass(SolrDocumentDescriptor::class)
            ->setStart(0)
            ->setRows(1)
            ->setQuery(SolrDocumentDescriptor::FIELD_DOC_ID.':"'.$documentId.'"');

        $filters = SolrSearchHelper::buildFilterQueries([
            'entitytype' => [
                'field' => SolrDocumentDescriptor::FIELD_ENTITY_TYPE,
                'value' => DocumentDescriptor::ENTITY_TYPE,
            ],
        ]);
        $select->addFilterQueries($filters);

        $resultSet = $client->select($select);

        if (1 !== $resultSet->count()) {
            return null;
        }
        /** @var SolrDocumentDescriptor $obj */
        $obj = $resultSet->getIterator()[0];

        return $obj->getDocumentDescriptor();
    }

    /**
     * @param string $visibility
     *
     * @return \Solarium\Client|null
     */
    private function getClientByDocumentVisibility($visibility)
    {
        if (DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE === $visibility) {
            $client = $this->coreService->getPrivateSolrClient();
        } elseif (DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC === $visibility) {
            $client = $this->coreService->getPublicSolrClient();
        } else {
            return null;
        }

        return $client;
    }
}
