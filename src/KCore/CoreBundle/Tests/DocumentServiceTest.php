<?php

namespace KCore\CoreBundle\Services;


use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Entity\InstitutionDescriptor;
use Solarium\QueryType\Update\Query\Document\Document;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class DocumentServiceTest extends WebTestCase {

    /** @var DocumentService $documentService */
    static $documentService;

    /**
     * Service Loading and Configuration
     */
    public static function setUpBeforeClass()
    {
        //start the symfony kernel
        /** @var KernelInterface $kernel */
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        //now we can instantiate our service (if you want a fresh one for
        //each test method, do this in setUp() instead
        self::$documentService = $container->get('klink.document.service');
    }

    /**
     * DocumentDescriptor dataProvider
     *
     * @param int $total
     * @return array
     */
    public static function generateDocumentDescriptors($testedMethod, $total = 3) {
        static $documentDescriptors = array();

        $faker = \Faker\Factory::create();
        if (empty($documentDescriptors)) {
            foreach (range(1, $total) as $i) {
                $institutionId = 'testInstitutionID';
                $docDesc = new DocumentDescriptor($institutionId, 'testDocumentID' . $faker->randomNumber());
                $docDesc->setVisibility(DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC);
                $docDesc->setDocumentURI($faker->url . '/document' . $i);
                $docDesc->setThumbnailURI($docDesc->getDocumentURI() . '/thumbnail');

                $docDesc->setCreationDate($faker->dateTimeThisMonth);
                $docDesc->setUserOwner($faker->firstName . ' Owner <'. $faker->safeEmail .'>');
                $docDesc->setUserUploader($faker->firstName . ' Uploader <'. $faker->safeEmail .'>');

                $docDesc->addAuthor($faker->firstName . ' Author0 <'. $faker->safeEmail .'>');
                $docDesc->addAuthor($faker->firstName . ' Author1 <'. $faker->companyEmail .'>');

                $docDesc->setTitle($faker->realText(50));
                $docDesc->setAbstract($faker->realText(300));
                $docDesc->setContents($faker->realText(500));

                $docDesc->setDocumentType('Presentation');
                $docDesc->setMimeType('application/pdf');
                $docDesc->setHash($faker->sha256 . $faker->sha256);
                $documentDescriptors[] = array($docDesc);
            }
        }
       return $documentDescriptors;
    }

    /**
     * @dataProvider generateDocumentDescriptors
     * @param DocumentDescriptor $document
     */
    public function testIndexDocumentDescriptor(DocumentDescriptor $document) {
        try {
            $response = self::$documentService->indexDocumentDescriptor($document);
            $this->assertEquals(0, $response->getStatus());
            $this->assertEquals(200, $response->getResponse()->getStatusCode());
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    /**
     * @dataProvider generateDocumentDescriptors
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testIndexDocumentDescriptor
     */
    public function testSearchDocumentDescriptor(DocumentDescriptor $documentDescriptor) {

        $terms = explode(' ', $documentDescriptor->getContents(), 5);
        array_pop($terms);

        // Performing a phrase search, with content keywords
        $response = self::$documentService->searchDocumentDescriptor('"'. implode(' ', $terms).'"');

        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals(200, $response->getResponse()->getStatusCode());
        $this->assertGreaterThanOrEqual(1, $response->count());
    }

    /**
     * @dataProvider generateDocumentDescriptors
     * @param DocumentDescriptor $document
     */
    public function testIdCoding(DocumentDescriptor $document) {
        $parts = DocumentDescriptor::splitDocumentId($document->getId());

        $this->assertEquals($parts, array(
            $document->getInstitutionId(),
            $document->getLocalDocumentId()
        ));
    }


    /**
     * @dataProvider generateDocumentDescriptors
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testIndexDocumentDescriptor
     */
    public function testGetDocumentById(DocumentDescriptor $documentDescriptor) {

        $doc1 = self::$documentService->getDocumentDescriptor($documentDescriptor->getInstitutionId(), $documentDescriptor->getLocalDocumentId());
        $this->assertNotNull($doc1);

        $doc2 = self::$documentService->getDocumentDescriptorById($documentDescriptor->getId());
        $this->assertNotNull($doc1);

        $this->assertEqualsDocumentDescriptors($doc2, $doc1);
    }

    /**
     * @dataProvider generateDocumentDescriptors
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testIndexDocumentDescriptor
     */
    public function testGetDocument(DocumentDescriptor $documentDescriptor) {

        $doc = self::$documentService->getDocumentDescriptor($documentDescriptor->getInstitutionId(), $documentDescriptor->getLocalDocumentId());
        $this->assertNotNull($doc);

        $this->assertEqualsDocumentDescriptors($documentDescriptor, $doc);
    }

    /**
     * @param DocumentDescriptor $doc1
     * @param DocumentDescriptor $doc2
     */
    function assertEqualsDocumentDescriptors(DocumentDescriptor $doc1, DocumentDescriptor $doc2) {
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
        $this->assertEquals($doc1->getThumbnailURI(), $doc2->getThumbnailURI());
        $this->assertEquals($doc1->getTitle(), $doc2->getTitle());
        $this->assertEquals($doc1->getUserUploader(), $doc2->getUserUploader());
        $this->assertEquals($doc1->getUserOwner(), $doc2->getUserOwner());
        $this->assertEquals($doc1->getLanguage(), $doc2->getLanguage());

        $this->assertEquals(count($doc1->getAuthors()), count($doc2->getAuthors()));
        foreach($doc1->getAuthors() as $author) {
            $this->assertTrue(in_array($author, $doc2->getAuthors()));
        }
    }

    /**
     * @dataProvider generateDocumentDescriptors
     * @param DocumentDescriptor $documentDescriptor
     * @depends      testGetDocument
     */
    public function testDeleteInstitution(DocumentDescriptor $documentDescriptor) {
        $response = self::$documentService->deleteDocumentDescriptor($documentDescriptor);
        $this->assertEquals(0, $response->getStatus());

        $inst = self::$documentService->getDocumentDescriptor($documentDescriptor->getInstitutionId(), $documentDescriptor->getLocalDocumentId());
        $this->assertNull($inst);
    }

}
 