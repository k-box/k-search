<?php

namespace KCore\DocumentAPIBundle\Tests\Controller;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Services\CoreService;
use KCore\CoreBundle\Services\DocumentService;
use KCore\CoreBundle\Services\LocationExtractorService;
use KCore\DocumentAPIBundle\Entity\Document;
use Pnz\GeoJSON\GeoJSONBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Serializer;

abstract class BaseDocumentAPITestClass extends WebTestCase
{
    /** @var Serializer $serializer */
    public static $serializer;

    /** @var DocumentService $documentService */
    public static $documentService;

    /** @var CoreService $coreService */
    public static $coreService;

    /** @var LocationExtractorService */
    public static $locationExtractorService;

    /** @var string */
    protected static $hashAlgorithm;

    public static $PUBLIC = DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC;
    public static $PRIVATE = DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE;

    public static $physicalDocs;

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

        //start the symfony kernel
        /** @var KernelInterface $kernel */
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        //now we can instantiate our service (if you want a fresh one for
        //each test method, do this in setUp() instead
        self::$serializer = $container->get('jms_serializer');

        self::$documentService = $container->get('klink.document.service');
        self::$coreService = $container->get('klink.core.service');
        self::$locationExtractorService = $container->get('klink.locationextractor.service');
        self::$hashAlgorithm = $container->getParameter('kcore.document_descriptor_hash');
    }

    /**
     * @param $institutionId
     * @param $localDocumentId
     * @param string $action
     * @param string $visibilityUrl
     * @param String $username
     * @param String $password
     *
     * @return null|Response
     */
    protected function doRequestDocumentDescriptor($visibilityUrl, $institutionId, $localDocumentId, $action, $username, $password)
    {
        $extra = ['CONTENT_TYPE' => 'application/json'];
        if ($username) {
            $extra['PHP_AUTH_USER'] = $username;
        }
        if ($password) {
            $extra['PHP_AUTH_PW'] = $password;
        }

        $client = static::createClient();
        $client->request(
          $action,
          'descriptors/'.$visibilityUrl.'/'.$institutionId.'/'.$localDocumentId,
          [],
          [],
          $extra
        );

        return $client->getResponse();
    }


    /**
     * @param $visibilityUrl
     * @param $institutionId
     * @param $localDocumentId
     * @param $username
     * @param $password
     *
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    protected function doDeleteDocumentDescriptor($visibilityUrl, $institutionId, $localDocumentId, $username, $password)
    {
        return $this->doRequestDocumentDescriptor($visibilityUrl, $institutionId, $localDocumentId, 'DELETE', $username, $password);
    }

    /**
     * @param $visibilityUrl
     * @param $institutionId
     * @param $localDocumentId
     * @param $username
     * @param $password
     *
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    protected function doGetDocumentDescriptor($visibilityUrl, $institutionId, $localDocumentId, $username, $password)
    {
        return $this->doRequestDocumentDescriptor($visibilityUrl, $institutionId, $localDocumentId, 'GET', $username, $password);
    }

    /**
     * @param Document $document
     * @param String   $username
     * @param String   $password
     *
     * @return Response|null
     */
    protected function doPostDocument(Document $document, $username, $password)
    {
        $extra = ['CONTENT_TYPE' => 'application/json'];
        if ($username) {
            $extra['PHP_AUTH_USER'] = $username;
        }
        if ($password) {
            $extra['PHP_AUTH_PW'] = $password;
        }

        $client = static::createClient();
        $client->request(
          'POST',
          'descriptors/',
          [],
          [],
          $extra,
          self::$serializer->serialize($document, 'json')
        );

        return $client->getResponse();
    }

    /**
     * @param DocumentDescriptor $doc1
     * @param DocumentDescriptor $doc2
     *
     * @see DocumentServiceTest::assertEqualsDocumentDescriptors()
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
        $this->assertEquals($doc1->getThumbnailURI(), $doc2->getThumbnailURI());
        $this->assertEquals($doc1->getTitle(), $doc2->getTitle());
        $this->assertEquals($doc1->getUserUploader(), $doc2->getUserUploader());
        $this->assertEquals($doc1->getUserOwner(), $doc2->getUserOwner());

        $this->assertEquals(count($doc1->getAuthors()), count($doc2->getAuthors()));
        foreach ($doc1->getAuthors() as $author) {
            $this->assertTrue(in_array($author, $doc2->getAuthors()));
        }

        $this->assertEquals($doc1->getLocationsString(), $doc2->getLocationsString());
        $this->assertEquals($doc1->getTitleAliases(), $doc2->getTitleAliases());

        $doc1Locs = $doc1->getLocations();
        $this->assertContainsOnlyInstancesOf('Pnz\GeoJSON\GeoJSONFeature', $doc1Locs);
        $doc2Locs = $doc2->getLocations();
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
     * @param string $institution
     * @param string $visibility
     * @param array  $documentFile
     * @param string $documentId
     *
     * @return \KCore\CoreBundle\Entity\DocumentDescriptor
     */
    public static function generateDocumentDescriptor($institution, $visibility, array $documentFile, $documentId = null)
    {
        if (empty($documentId)) {
            $documentId = uniqid();
        }

        $faker = \Faker\Factory::create();
        $docDesc = new DocumentDescriptor($institution, $documentId);
        $docDesc->setVisibility($visibility);
        $docDesc->setDocumentURI($documentFile['uri']);
        $docDesc->setThumbnailURI($docDesc->getDocumentURI().'/thumbnail');

        $docDesc->setCreationDate($faker->dateTimeThisMonth);
        $docDesc->setUserOwner($faker->firstName.' Owner <'.$faker->safeEmail.'>');
        $docDesc->setUserUploader($faker->firstName.' Uploader <'.$faker->safeEmail.'>');

        $docDesc->addAuthor($faker->firstName.' Author0 <'.$faker->safeEmail.'>');
        $docDesc->addAuthor($faker->firstName.' Author1 <'.$faker->companyEmail.'>');

        $docDesc->setTitle($faker->sentence(6));
        $docDesc->setAbstract($faker->paragraph(5));

        $docDesc->setDocumentType('Presentation');
        $docDesc->setMimeType($documentFile['mimeType']);

        $docDesc->setContents($faker->text(100));
        $docDesc->setHash($documentFile['hash']);

        $docDesc->addTitleAlias('title_alias_01 '.$faker->randomNumber());
        $docDesc->addTitleAlias('title_alias_02 '.$faker->randomNumber());

        // Adding Locations
        $geoJSONFeature = GeoJSONBuilder::buildGeoJSONFeature(GeoJSONBuilder::buildGeoJSONGeometryPoint());
        $geoJSONFeature
          ->setProperty('name', 'GeoLocation1')
          ->getGeometry()->addCoordinatePoint(72.6373549, 40.3067574);
        $docDesc->addLocation($geoJSONFeature);

        return $docDesc;
    }

    /**
     * @param $institution
     * @param $visibility
     * @param $documentFile
     * @param null $documentId
     *
     * @return \KCore\DocumentAPIBundle\Entity\Document
     */
    public static function generateDocument($institution, $visibility, $documentFile, $documentId = null)
    {
        $descriptor = self::generateDocumentDescriptor($institution, $visibility, $documentFile, $documentId);
        $document = new Document();
        $document->setDocumentDescriptor($descriptor);
        $document->setDocumentData($documentFile['base64']);

        return $document;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public static function getPhysicalDocs($type = null)
    {
        $files = [
            'file.pdf' => 'application/pdf',
            'file.txt' => 'text/plain',
        ];

        if (count(self::$physicalDocs) == 0) {
            self::$physicalDocs = [];

            foreach ($files as $fileName => $type) {
                $contents = file_get_contents(__DIR__.'/../files/'.$fileName);
                self::$physicalDocs[$type] = [
                    'mimeType' => $type,
                    'uri' => 'https://example.url/'.$fileName,
                    'base64' => base64_encode($contents),
                    'hash' => hash(self::$hashAlgorithm, $contents),
                ];
            }
        }

        if ($type) {
            if (array_key_exists($type, self::$physicalDocs)) {
                return self::$physicalDocs[$type];
            } else {
                return [];
            }
        }

        return self::$physicalDocs;
    }

    /**
     * @param $docUri
     */
    public static function getBase64FromDocUri($docUri)
    {
        foreach (self::$physicalDocs as $physicalDoc) {
            if ($physicalDoc['uri'] === $docUri) {
                return $physicalDoc['base64'];
            }
        }
    }

    /**
     * @param Response $response
     * @param int      $statusCode
     * @param bool     $checkValidJson
     * @param string   $contentType
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
