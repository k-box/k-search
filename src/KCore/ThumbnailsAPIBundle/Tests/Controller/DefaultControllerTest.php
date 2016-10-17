<?php

namespace KCore\ThumbnailsAPIBundle\Tests\Controller;

use JMS\Serializer\Serializer;
use KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest;
use KCore\ThumbnailsAPIBundle\Services\ThumbnailsService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @group thumbnails
 */
class DefaultControllerTest extends WebTestCase
{
    /** @var Serializer */
    public static $serializer;

    /** @var ThumbnailsService */
    public static $thumbnailsService;

    /** @var Filesystem */
    public static $fs;

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
        self::$thumbnailsService = $container->get('klink.thumbnails.service');

        self::$fs = new Filesystem();
    }

    protected function setUp()
    {
        self::$thumbnailsService->clearThumbnailsCache();
    }

    public function testGetThumbnailReturn404IfMissing()
    {
        $institutionID = 'test';
        $localDocumentID = '42';

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 404);
    }

    public function testGetThumbnailReturn102IfQueued()
    {
        $institutionID = 'test';
        $localDocumentID = '40';

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__.'/Test.pdf');

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();
        $this->assertEquals(102, $response->getStatusCode());
    }
    /*
    public function testGetThumbnailReturn400IfWrongInstitutionId()
    {
        $institutionID = " ";
        $localDocumentID = "41";

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__ . "/Test.pdf");

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/' . $institutionID . '/' . $localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 400);
    }
    */

    /*
    public function testGetThumbnailReturn400IfWrongDocumentId()
    {
        $institutionID = "test";
        $localDocumentID = " ";

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__ . "/Test.pdf");

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/' . $institutionID . '/' . $localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 400);
    }
    */

    /**
     * @group java
     */
    public function testQueueAndGetThumbnailForPDFFile()
    {
        $institutionID = 'test';
        $localDocumentID = '42';

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__.'/Test.pdf');
        self::$thumbnailsService->generateNextThumbnailInQueue();

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 200);
    }

    public function testQueueAndGetThumbnailForXlsxFile()
    {
        $institutionID = 'test';
        $localDocumentID = '43';

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__.'/Test.xlsx');
        self::$thumbnailsService->generateNextThumbnailInQueue();

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 200);
    }

    public function testQueueAndGetThumbnailForOdtFile()
    {
        $institutionID = 'test';
        $localDocumentID = '44';

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__.'/Test.odt');
        self::$thumbnailsService->generateNextThumbnailInQueue();

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 200);
    }

    public function testQueueAndGetThumbnailForURI()
    {
        $institutionID = 'test';
        $localDocumentID = '45';

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__.'/Test.uri');
        self::$thumbnailsService->generateNextThumbnailInQueue();

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 200);
    }

    /**
     * @group java
     */
    public function testGetThumbnailDoDeletePng()
    {
        $institutionID = 'test';
        $localDocumentID = '42';

        self::$thumbnailsService->reserveThumbnailCreation($localDocumentID, $institutionID, __DIR__.'/Test.pdf');
        self::$thumbnailsService->generateNextThumbnailInQueue();

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();

        $client = static::createClient();
        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 404);
    }

    /**
     * @group java
     */
    public function testGenerateThumbnailFromPDF()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileName('Nome.pdf');
        $request->setFileMime('application/pdf');
        $request->setFileData(base64_encode(file_get_contents(__DIR__.'/Test.pdf')));

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 201);
    }

    public function testGenerateThumbnailFromUri()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileName('test.uri');
        $request->setFileMime('text/uri-list');
        $request->setFileData(base64_encode("# This is a comment\r\nhttp://xkcd.com"));

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 201);
    }

    public function testGenerateThumbnailFromWrongUriList()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileName('wrong.uri');
        $request->setFileMime('text/uri-list');
        $request->setFileData(base64_encode("http://xkcd.com\r\nhttp://google.com"));

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 400);
    }

    public function testGenerateThumbnailDoDeleteFiles()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileName('Nome.pdf');
        $request->setFileMime('application/pdf');
        $request->setFileData(base64_encode(file_get_contents(__DIR__.'/Test.pdf')));

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();

        $finder = new Finder();
        $finder->files()->in(self::$thumbnailsService->getThumbnailsQueuePath());
        $this->assertTrue($finder->count() == 0);

        $finder = new Finder();
        $finder->files()->in(self::$thumbnailsService->getThumbnailsLocksPath());
        $this->assertTrue($finder->count() == 0);

        $finder = new Finder();
        $finder->files()->in(self::$thumbnailsService->getThumbnailsImagesPath());
        $this->assertTrue($finder->count() == 0);
    }

    public function testGenerateThumbnailFailIfWrongName()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileMime('application/pdf');
        $request->setFileData(base64_encode(file_get_contents(__DIR__.'/Test.pdf')));

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 400);
    }

    public function testGenerateThumbnailFailIfWrongData()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileName('Nome.pdf');
        $request->setFileMime('application/pdf');

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 400);
    }

    public function testGenerateThumbnailFailIfUnsupportedMime()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileName('Nome.pdf');
        $request->setFileMime('text/xml');
        $request->setFileData(base64_encode(file_get_contents(__DIR__.'/Test.pdf')));

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 400);
    }

    public function testGenerateThumbnailFailIfUnsupportedExtension()
    {
        $request = new ThumbnailGeneratorRequest();
        $request->setFileName('Nome.json');
        $request->setFileData(base64_encode(file_get_contents(__DIR__.'/Test.pdf')));

        $client = static::createClient();
        $client->request(
            'POST',
            'thumbnails/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            self::$serializer->serialize($request, 'json')
        );
        $response = $client->getResponse();
        $this->assertJsonResponse($response, 400);
    }

    public function testDeleteExpiredFiles()
    {
        $now = new \DateTime();
        $timestamp = $now->getTimestamp() - (self::$thumbnailsService->getExpireTime() * 60 + 1);

        $queueFile = self::$thumbnailsService->getThumbnailsQueuePath().'/'.$timestamp.'_theanswer.pdf';
        $lockFile = self::$thumbnailsService->getThumbnailsLocksPath().'/'.$timestamp.'_theanswer.lock';
        $thumbnailFile = self::$thumbnailsService->getThumbnailsImagesPath().'/'.$timestamp.'_theanswer.png';

        self::$fs->touch($queueFile);
        self::$fs->touch($lockFile);
        self::$fs->touch($thumbnailFile);

        self::$thumbnailsService->deleteExpiredFiles();

        $this->assertFalse(self::$fs->exists($queueFile));
        $this->assertFalse(self::$fs->exists($lockFile));
        $this->assertFalse(self::$fs->exists($thumbnailFile));
    }

    public function testKeepNonExpiredFiles()
    {
        $now = new \DateTime();
        $timestamp = $now->getTimestamp() - (self::$thumbnailsService->getExpireTime() * 60 - 5);

        $queueFile = self::$thumbnailsService->getThumbnailsQueuePath().'/'.$timestamp.'_nonexpired.pdf';
        $lockFile = self::$thumbnailsService->getThumbnailsLocksPath().'/'.$timestamp.'_nonexpired.lock';
        $thumbnailFile = self::$thumbnailsService->getThumbnailsImagesPath().'/'.$timestamp.'_nonexpired.png';

        self::$fs->touch($queueFile);
        self::$fs->touch($lockFile);
        self::$fs->touch($thumbnailFile);

        self::$thumbnailsService->deleteExpiredFiles();

        $this->assertTrue(self::$fs->exists($queueFile));
        $this->assertTrue(self::$fs->exists($lockFile));
        $this->assertTrue(self::$fs->exists($thumbnailFile));
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
