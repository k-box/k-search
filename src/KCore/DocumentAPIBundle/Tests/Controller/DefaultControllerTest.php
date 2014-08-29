<?php

namespace KCore\DocumentAPIBundle\Tests\Controller;

use JMS\Serializer\Serializer;
use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\DocumentAPIBundle\Entity\Document;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class DefaultControllerTest extends WebTestCase
{

    /** @var Serializer $serializer */
    static $serializer;

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
        self::$serializer = $container->get('jms_serializer');
    }

    /**
     * @param $institutionId
     * @param $localDocumentId
     * @param string $action
     * @return Response|null
     */
    protected function doRequestDocumentDescriptor($institutionId, $localDocumentId, $action = 'GET')
    {
        $client = static::createClient();
        $client->request(
            $action,
            'kcore/descriptors/' . $institutionId . '/' . $localDocumentId,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json')
        );

        return $client->getResponse();
    }

    /**
     * @param Document $document
     * @return Response|null
     */
    protected function doPostDocument(Document $document)
    {
        $client = static::createClient();
        $client->request(
            'POST',
            'kcore/descriptors/',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            self::$serializer->serialize($document, 'json')
        );

        return $client->getResponse();
    }

    public static function _getFisicalDocs() {
        return array(
            array(
                'mimeType' => 'application/pdf',
                'uri' => 'http://www.klink.dyndns.ws/temp/file.pdf',
            ),
            array(
                'mimeType' => 'text/plain',
                'uri' => 'http://www.klink.dyndns.ws/temp/file-readme.pdf',
            ),
        );
    }

    /**
     * Document dataProvider
     *
     * @param int $total
     * @return DocumentDescriptor[]
     * @see DocumentServiceTest::generateDocumentDescriptors()
     */
    public static function _generateDocumentDescriptors($total = 5) {
        $documentsDesc = array();
        $faker = \Faker\Factory::create();
        $docs = static::_getFisicalDocs();

        foreach (range(1, $total) as $i) {
            $doc = $docs[$i % count($docs)];
            $institutionId = 'testInstitutionID';
            $docDesc = new DocumentDescriptor($institutionId, 'testDocumentID' . $i);
            $docDesc->setVisibility(DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC);
            $docDesc->setDocumentURI($doc['uri']);
            $docDesc->setThumbnailURI($docDesc->getDocumentURI() . '/thumbnail');

            $docDesc->setCreationDate($faker->dateTimeThisMonth);
            $docDesc->setUserOwner($faker->firstName . ' Owner <'. $faker->safeEmail .'>');
            $docDesc->setUserUploader($faker->firstName . ' Uploader <'. $faker->safeEmail .'>');

            $docDesc->addAuthor($faker->firstName . ' Author0 <'. $faker->safeEmail .'>');
            $docDesc->addAuthor($faker->firstName . ' Author1 <'. $faker->companyEmail .'>');

            $docDesc->setTitle($faker->sentence(6));
            $docDesc->setAbstract($faker->paragraph(5));
            $docDesc->setContents($faker->text(500));

            $docDesc->setDocumentType('Presentation');
            $docDesc->setMimeType($doc['mimeType']);
            $docDesc->setHash($faker->sha256 . $faker->sha256);
            $documentDescriptors[] = array($docDesc);

            $documentsDesc[] = array($docDesc);
        }
        return $documentsDesc;
    }

    /**
     * @param DocumentDescriptor $doc1
     * @param DocumentDescriptor $doc2
     * * @see DocumentServiceTest::assertEqualsDocumentDescriptors()
     */
    function _assertEqualsDocumentDescriptors(DocumentDescriptor $doc1, DocumentDescriptor $doc2) {
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

        $this->assertEquals(count($doc1->getAuthors()), count($doc2->getAuthors()));
        foreach($doc1->getAuthors() as $author) {
            $this->assertTrue(in_array($author, $doc2->getAuthors()));
        }
    }

    /**
     * @param int $itemsNum
     * @return DocumentDescriptor[]
     */
    public function generateDocumentDescriptors($itemsNum = 4) {
        static $items = array();
        if (empty($items)) {
            $items = $this->_generateDocumentDescriptors($itemsNum, true);
        }
        return $items;
    }

    /**
     * @param DocumentDescriptor $documentDescriptor
     * @dataProvider generateDocumentDescriptors
     */
    public function testPostDocument(DocumentDescriptor $documentDescriptor)
    {
        $document = new Document();
        $document->setDocumentDescriptor($documentDescriptor);
        $document->setDocumentData(base64_encode(file_get_contents($documentDescriptor->getDocumentURI())));
        $this->assertNotEmpty($document->getDocumentData());

        $response = $this->doPostDocument($document);

        $this->assertJsonResponse($response, 201);

        $this->assertEquals(
            '/kcore/descriptors/' . $documentDescriptor->getInstitutionId() . '/' . $documentDescriptor->getLocalDocumentId(),
            $response->headers->get('Location')
        );
    }

    /**
     * @depends testPostDocument
     * @dataProvider generateDocumentDescriptors
     */
    public function testGetDocument(DocumentDescriptor $documentDescriptor)
    {
        $response = $this->doRequestDocumentDescriptor($documentDescriptor->getInstitutionId(), $documentDescriptor->getLocalDocumentId());
        $this->assertJsonResponse($response);

        /** @var DocumentDescriptor $obj */
        $doc = self::$serializer->deserialize($response->getContent(), 'KCore\CoreBundle\Entity\DocumentDescriptor', 'json');

        $this->_assertEqualsDocumentDescriptors($documentDescriptor, $doc);
    }

    /**
     * @param DocumentDescriptor $documentDesc
     * @depends testGetDocument
     * @dataProvider generateDocumentDescriptors
     */
    public function testDeleteDocumentDescriptor(DocumentDescriptor $documentDesc)
    {
        $response = $this->doRequestDocumentDescriptor(
            $documentDesc->getInstitutionId(),
            $documentDesc->getLocalDocumentId(),
            'DELETE');
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @param Response $response
     * @param int $statusCode
     * @param bool $checkValidJson
     * @param string $contentType
     */
    protected function assertJsonResponse(Response $response, $statusCode = 200, $checkValidJson = true, $contentType = 'application/json')
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($contentType, $response->headers->get('Content-Type'));

        if ($checkValidJson) {
            $decode = json_decode($response->getContent());
            $this->assertTrue(($decode != null && $decode != false));
        }
    }
}
