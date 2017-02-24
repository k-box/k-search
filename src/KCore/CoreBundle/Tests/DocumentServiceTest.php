<?php

namespace KCore\CoreBundle\Services;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use Pnz\GeoJSON\GeoJSONBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DocumentServiceTest.
 *
 * @group solr
 */
class DocumentServiceTest extends WebTestCase
{
    /** @var DocumentService $documentService */
    public static $documentService;

    /** @var CoreService $coreService */
    public static $coreService;

    /** @var string */
    public static $hashAlgorithm;

    public static function setUpBeforeClass()
    {
        self::init();
    }

    /**
     * Service and configuration loading.
     */
    protected static function init()
    {
        if (!empty(self::$coreService)) {
            return;
        }
        /** @var KernelInterface $kernel */
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();
        self::$hashAlgorithm = $container->getParameter('kcore.document_descriptor_hash');

        //now we can instantiate our service (if you want a fresh one for
        //each test method, do this in setUp() instead
        self::$coreService = $container->get('klink.core.service');
        self::$documentService = $container->get('klink.document.service');
        self::$documentService->deleteAllDocumentDescriptors();
    }

    public static function generateDocumentDescriptor($i, $visibility) {
        $faker = \Faker\Factory::create();

        $docDesc = new DocumentDescriptor('testInstitutionID', $visibility.'TestDocumentID'.$faker->randomNumber());
        $docDesc->setVisibility($visibility);
        $docDesc->setDocumentURI($faker->url.'/document'.$i);
        $docDesc->setThumbnailURI($docDesc->getDocumentURI().'/thumbnail');

        $docDesc->setCreationDate($faker->dateTimeThisMonth);
        $docDesc->setUpdateDate($faker->dateTimeThisMonth);

        $docDesc->setUserOwner($faker->firstName.' Owner <'.$faker->safeEmail.'>');
        $docDesc->setUserUploader($faker->firstName.' Uploader <'.$faker->safeEmail.'>');

        $docDesc->addAuthor($faker->firstName.' Author0 <'.$faker->safeEmail.'>');
        $docDesc->addAuthor($faker->firstName.' Author1 <'.$faker->companyEmail.'>');

        $docDesc->addTitleAlias('title_alias_0 '.$faker->randomNumber());
        $docDesc->addTitleAlias('title_alias_1 '.$faker->randomNumber());

        $geoJSONFeature = GeoJSONBuilder::buildGeoJSONFeature(GeoJSONBuilder::buildGeoJSONGeometryPoint());
        $geoJSONFeature
            ->setProperty('name', 'Bishkek')
            ->getGeometry()->addCoordinatePoint(72.6373549, 40.3067574);
        $docDesc->addLocation($geoJSONFeature);

        $docDesc->addTopicTerm('test_topic_term_0');
        $docDesc->addTopicTerm('test_topic_term_1');

        $docDesc->addDocumentFolder('/test/doc/folder/folder0/subfolder0');
        $docDesc->addDocumentFolder('/test/doc/folder/folder1/subfolder1');

        $docDesc->addDocumentGroup('1:1');
        $docDesc->addDocumentGroup('1:2');

        $docDesc->setTitle($faker->realText(50));
        $docDesc->setAbstract($faker->realText(300));
        $text = $faker->realText(500);
        $docDesc->setContents($text);
        $docDesc->setLanguage('en');

        $docDesc->setDocumentType('Presentation');
        $docDesc->setMimeType('application/pdf');
        $docDesc->setHash(hash(self::$hashAlgorithm, $text));

        $docDesc->addProjectId('123');
        $docDesc->addProjectId('456');

        return $docDesc;
    }

    /**
     * DocumentDescriptor dataProvider.
     *
     * @param string $testedMethod
     * @param int    $totalPublic
     * @param int    $totalPrivate
     *
     * @return array
     */
    public static function generateDocumentDescriptors($testedMethod, $totalPublic = 4, $totalPrivate = 4)
    {
        self::init();
        static $documentDescriptors = [];

        if (empty($documentDescriptors)) {
            for ($i = 1; $i <= $totalPublic; ++$i) {
                $docDesc = self::generateDocumentDescriptor($i, DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC);
                $documentDescriptors[] = [$docDesc];
            }
            for ($i = 1; $i <= $totalPrivate; ++$i) {
                $docDesc = self::generateDocumentDescriptor($i, DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE);
                $documentDescriptors[] = [$docDesc];
            }
        }

        return $documentDescriptors;
    }

    public function testIndexDocumentDescriptorEmptyUpdatedDate()
    {
        $document = self::generateDocumentDescriptor(1, DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE);
        $document->setUpdateDate(null);

        $response = self::$documentService->indexDocumentDescriptor($document);
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals(200, $response->getResponse()->getStatusCode());

        $doc2 = self::$documentService->getDocumentDescriptorById(
            $document->getId(),
            $document->getVisibility());

        $document->setUpdateDate($document->getCreationDate());
        $this->assertEqualsDocumentDescriptors($document, $doc2);
    }


    /**
     * @dataProvider generateDocumentDescriptors
     *
     * @param DocumentDescriptor $document
     */
    public function testIndexDocumentDescriptor(DocumentDescriptor $document)
    {
        try {
            // Overriding the Language during indexing
            $language = $document->getLanguage();
            $document->setLanguage(null);

            $response = self::$documentService->indexDocumentDescriptor($document);
            $this->assertEquals(0, $response->getStatus());
            $this->assertEquals(200, $response->getResponse()->getStatusCode());

            $document->setLanguage($language);
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    /**
     * @dataProvider generateDocumentDescriptors
     *
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testIndexDocumentDescriptor
     */
    public function testSearchDocumentDescriptor(DocumentDescriptor $documentDescriptor)
    {
        $terms = explode(' ', $documentDescriptor->getContents(), 5);
        array_pop($terms);

        // Performing a phrase search, with content keywords
        $response = self::$documentService->searchDocumentDescriptor('"'.implode(' ', $terms).'"', $documentDescriptor->getVisibility());

        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals(200, $response->getResponse()->getStatusCode());
        $this->assertGreaterThanOrEqual(1, $response->count());
    }

    /**
     * @dataProvider generateDocumentDescriptors
     *
     * @param DocumentDescriptor $document
     */
    public function testIdCoding(DocumentDescriptor $document)
    {
        $parts = DocumentDescriptor::splitDocumentId($document->getId());

        $this->assertEquals($parts, [
            $document->getInstitutionId(),
            $document->getLocalDocumentId(),
        ]);
    }

    /**
     * @dataProvider generateDocumentDescriptors
     *
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testIndexDocumentDescriptor
     */
    public function testGetDocumentById(DocumentDescriptor $documentDescriptor)
    {
        $doc1 = self::$documentService->getDocumentDescriptor(
            $documentDescriptor->getInstitutionId(),
            $documentDescriptor->getLocalDocumentId(),
            $documentDescriptor->getVisibility()
        );
        $this->assertNotNull($doc1);

        $doc2 = self::$documentService->getDocumentDescriptorById(
            $documentDescriptor->getId(),
            $documentDescriptor->getVisibility());
        $this->assertNotNull($doc1);

        $this->assertEqualsDocumentDescriptors($doc2, $doc1);
    }

    /**
     * @dataProvider generateDocumentDescriptors
     *
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testIndexDocumentDescriptor
     */
    public function testGetDocument(DocumentDescriptor $documentDescriptor)
    {
        $doc = self::$documentService->getDocumentDescriptor(
            $documentDescriptor->getInstitutionId(),
            $documentDescriptor->getLocalDocumentId(),
            $documentDescriptor->getVisibility()
        );
        $this->assertNotNull($doc);

        $this->assertEqualsDocumentDescriptors($documentDescriptor, $doc);
    }

    /**
     * @param DocumentDescriptor $doc1
     * @param DocumentDescriptor $doc2
     */
    public function assertEqualsDocumentDescriptors(DocumentDescriptor $doc1, DocumentDescriptor $doc2)
    {
        // Checking Entity data
        $this->assertEquals($doc1->getEntityId(), $doc2->getEntityId());
        $this->assertEquals($doc1->getEntityType(), $doc2->getEntityType());

        // Checking DocumentDescriptor data
        $this->assertEquals($doc1->getId(), $doc2->getId());
        $this->assertEquals($doc1->getInstitutionId(), $doc2->getInstitutionId());
        $this->assertEquals($doc1->getLocalDocumentId(), $doc2->getLocalDocumentId());
        $this->assertEquals($doc1->getDocumentType(), $doc2->getDocumentType());
        $this->assertEquals($doc1->getMimeType(), $doc2->getMimeType());

        $this->assertEquals($doc1->getDocumentURI(), $doc2->getDocumentURI());
        $this->assertEquals($doc1->getHash(), $doc2->getHash());
        $this->assertEquals($doc1->getCreationDate(), $doc2->getCreationDate());
        $this->assertEquals($doc1->getUpdateDate(), $doc2->getUpdateDate());
        $this->assertEquals($doc1->getThumbnailURI(), $doc2->getThumbnailURI());
        $this->assertEquals($doc1->getTitle(), $doc2->getTitle());
        $this->assertEquals($doc1->getAbstract(), $doc2->getAbstract());
        $this->assertEquals($doc1->getUserUploader(), $doc2->getUserUploader());
        $this->assertEquals($doc1->getUserOwner(), $doc2->getUserOwner());
        $this->assertEquals($doc1->getLanguage(), $doc2->getLanguage());

        $this->assertEquals(count($doc1->getAuthors()), count($doc2->getAuthors()));
        foreach ($doc1->getAuthors() as $author) {
            $this->assertTrue(in_array($author, $doc2->getAuthors()));
        }

        $this->assertEquals($doc1->getLocationsString(), $doc2->getLocationsString());
        $this->assertEquals($doc1->getTitleAliases(), $doc2->getTitleAliases());

        $doc1Locs = $doc1->getLocations();
        $doc2Locs = $doc2->getLocations();

        $this->assertContainsOnlyInstancesOf('Pnz\GeoJSON\GeoJSONFeature', $doc1Locs);
        $this->assertContainsOnlyInstancesOf('Pnz\GeoJSON\GeoJSONFeature', $doc2Locs);
        $this->assertEquals($doc1Locs, $doc2Locs);
        for ($i = 0; $i < count($doc1Locs); ++$i) {
            $this->assertEquals($doc1Locs[$i], $doc2Locs[$i]);
        }

        $this->assertEquals($doc1->getTopicTerms(), $doc2->getTopicTerms());
        $this->assertEquals($doc1->getDocumentFolders(), $doc2->getDocumentFolders());
        $this->assertEquals($doc1->getDocumentGroups(), $doc2->getDocumentGroups());
    }

    /**
     * @dataProvider generateDocumentDescriptors
     *
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testGetDocument
     */
    public function testDeleteInstitution(DocumentDescriptor $documentDescriptor)
    {
        $response = self::$documentService->deleteDocumentDescriptor($documentDescriptor);
        $this->assertEquals(0, $response->getStatus());

        $inst = self::$documentService->getDocumentDescriptor($documentDescriptor->getInstitutionId(), $documentDescriptor->getLocalDocumentId());
        $this->assertNull($inst);
    }

    /**
     * @return array
     */
    public function getAvailableFacets()
    {
        return [
            ['language', '\KCore\CoreBundle\Search\Facets\LanguageFacet'],
            ['documentType', '\KCore\CoreBundle\Search\Facets\DocumentTypeFacet'],
            ['institutionId', '\KCore\CoreBundle\Search\Facets\InstitutionIdFacet'],
            ['documentGroups', '\KCore\CoreBundle\Search\Facets\DocumentGroupsFacet'],
            ['documentGroups', '\KCore\CoreBundle\Search\Facets\DocumentGroupsFacet'],
            ['locationsString', '\KCore\CoreBundle\Search\Facets\LocationsStringFacet'],
            ['projectId',      '\KCore\CoreBundle\Search\Facets\DocumentProjectIdFacet'],
        ];
    }
    /**
     * @dataProvider getAvailableFacets
     */
    public function testAvailableFacets($facetName, $facetClass)
    {
        $facet = self::$documentService->createFacet($facetName);
        $this->assertInstanceOf($facetClass, $facet);
    }

    public function getAvailableFilters()
    {
        return [
            ['language',       '\KCore\CoreBundle\Search\Filters\LanguageFilter'],
            ['documentType',   '\KCore\CoreBundle\Search\Filters\DocumentTypeFilter'],
            ['institutionId',  '\KCore\CoreBundle\Search\Filters\InstitutionIdFilter'],
            ['documentGroups', '\KCore\CoreBundle\Search\Filters\DocumentGroupsFilter'],
            ['documentId',     '\KCore\CoreBundle\Search\Filters\DocumentIdFilter'],
            ['localDocumentId', '\KCore\CoreBundle\Search\Filters\LocalDocumentIdFilter'],
            ['locationsString', '\KCore\CoreBundle\Search\Filters\LocationsStringFilter'],
            ['projectId',      '\KCore\CoreBundle\Search\Filters\DocumentProjectIdFilter'],
        ];
    }

    /**
     * @dataProvider getAvailableFilters
     *
     * @param $filterName
     * @param $filterClass
     */
    public function testAvailableFilters($filterName, $filterClass)
    {
        $filter = self::$documentService->createFilter($filterName);
        $this->assertInstanceOf($filterClass, $filter);
    }

    /**
     * @return array
     */
    public function locationExtractionProvider()
    {
        return [
            [
                ['Bishkek'],
                "Hello, I'm a fake user from Bishkek.",
            ],
        ];
    }

    /**
     * Test LocationExtractor feature.
     *
     * @group location-extraction
     * @dataProvider locationExtractionProvider
     */
    public function testLocationExtraction($expected, $contents)
    {
        if (empty(self::$documentService->getLocationExtractorService()) ||
            empty(self::$documentService->getLocationExtractorService()->getServerUrl())) {
            $this->markTestSkipped('Missing LocationExtractorService configuration');
        }

        $docDesc = new DocumentDescriptor('testInstitutionID', 'TestDocumentID');
        $docDesc->setVisibility('public');
        $docDesc->setContents($contents);

        self::$documentService->extractAndAddLocationsToDocumentDescriptor($docDesc, $docDesc->getContents());

        $this->assertCount(count($expected), $docDesc->getLocationsString());
        $this->assertCount(count($expected), $docDesc->getLocations());

        foreach ($docDesc->getLocations() as $location) {
            $this->assertTrue(in_array($location->getProperty('name'), $expected));
            $this->assertTrue(in_array($location->getProperty('name'), $docDesc->getLocationsString()));
        }
    }

    public function dataProviderHashContents()
    {
        return [
            [true, hash(self::$hashAlgorithm, 'nice contents'), 'nice contents'],
            [false, hash(self::$hashAlgorithm, 'nice contents'), 'this is not a so nice content string'],

        ];
    }

    /**
     * @param $expected
     * @param $hash
     * @param $contents
     *
     * @dataProvider dataProviderHashContents
     */
    public function testHashContents($expected, $hash, $contents)
    {
        $this->assertEquals($expected, self::$documentService->validateDocumentDescriptorHashFromContents(
            $hash,
            $contents
        ));
    }
}
