<?php

namespace KCore\ThumbnailsAPIBundle\Tests\Controller;

use KCore\ThumbnailsAPIBundle\Services\ThumbnailsService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @group thumbnails
 */
class DefaultControllerTest extends WebTestCase
{
    /** @var string */
    protected $fixturesDir;

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
        self::$thumbnailsService = $container->get('klink.thumbnails.service');

        self::$fs = new Filesystem();
    }

    protected function setUp()
    {
        self::$thumbnailsService->clearThumbnailsCache();
        $this->fixturesDir = __DIR__.'/../fixtures/';
    }

    /*
    public function testNotAuthorizedGenerateThumbnail()
    {
        $response = $this->doGetThumbnail('test', '42', 'aaa', 'bbb');
        $this->assertJsonResponse($response, 401, false);
    }

    public function testGetThumbnailReturn404IfMissing()
    {
        $response = $this->doGetThumbnail('test', '42', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 404, false);
    }
    */

    /*
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
    */

    /**
     * @group pdfbox
     */
    public function testGenerateThumbnailFromPDF()
    {
        $file = $this->fixturesDir.'test.pdf';
        $contents = base64_encode(file_get_contents($file));
        $response = $this->doPostThumbnail($contents, 'test.pdf', 'application/pdf', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 201);
    }

    /**
     * @group phantomjs
     */
    public function testGenerateThumbnailFromUriWithComment()
    {
        $contents = base64_encode("#This is a comment\n\rhttps://httpbin.org/status/200");
        $response = $this->doPostThumbnail($contents, 'test.uri', 'text/uri-list', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 201);
    }

    /**
     * @group phantomjs
     */
    public function testGenerateThumbnailFromUri()
    {
        $contents = base64_encode("https://httpbin.org/status/200");
        $response = $this->doPostThumbnail($contents, 'test.uri', 'text/uri-list', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 201);
    }

    /**
     * @group phantomjs
     */
    public function testGenerateThumbnailFromUriWithRedirect()
    {
        $contents = base64_encode("https://httpbin.org/absolute-redirect/1");
        $response = $this->doPostThumbnail($contents, 'test.uri', 'text/uri-list', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 201);
    }

    /**
     * @group phantomjs
     */
    public function testGenerateThumbnailFromUriWithTooManyRedirects()
    {
        $contents = base64_encode("https://httpbin.org/absolute-redirect/5");
        $response = $this->doPostThumbnail($contents, 'test.uri', 'text/uri-list', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 509);
    }

    /**
     * @group phantomjs
     */
    public function testGenerateThumbnailFromUriWithTimeout()
    {
        $contents = base64_encode("https://httpbin.org/delay/6");
        $response = $this->doPostThumbnail($contents, 'test.uri', 'text/uri-list', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 504);
    }

    /**
     * @group phantomjs
     */
    public function testGenerateThumbnailFromUriUnknownResponse()
    {
        $contents = base64_encode("https://httpbin.org/status/418");
        $response = $this->doPostThumbnail($contents, 'test.uri', 'text/uri-list', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 502);
    }

    /**
     * @group phantomjs
     */
    public function testGenerateThumbnailFromWrongUriList()
    {
        $contents = base64_encode("http://xkcd.com\r\nhttp://google.com");
        $response = $this->doPostThumbnail($contents, 'wrong.uri', 'text/uri-list', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 400);
    }

    /*
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
    */

    /**
     * @group pdfbox
     */
    public function testGenerateThumbnailFailIfEmptyName()
    {
        $file = $this->fixturesDir.'test.pdf';
        $contents = base64_encode(file_get_contents($file));
        $response = $this->doPostThumbnail($contents, null, 'application/pdf', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 400);
    }

    /**
     * @group pdfbox
     */
    public function testGenerateThumbnailFailIfEmptyData()
    {
        $response = $this->doPostThumbnail(null, 'test.wrong-ext', null, 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 400);
    }

    /**
     * @group pdfbox
     */
    public function testGenerateThumbnailFailIfUnsupportedMime()
    {
        $file = $this->fixturesDir.'test.pdf';
        $contents = base64_encode(file_get_contents($file));
        $response = $this->doPostThumbnail($contents, 'test.pdf', 'text/xml', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 400);
    }

    /**
     * @group pdfbox
     */
    public function testGenerateThumbnailFailIfUnsupportedFileExtension()
    {
        $file = $this->fixturesDir.'test.pdf';
        $contents = base64_encode(file_get_contents($file));
        $response = $this->doPostThumbnail($contents, 'test.wrong-ext', null, 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 400);
    }

    /*
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
    */

    /*
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
    */

    /*
     * Helper functions
     */

    /**
     * Helper function to return the generated thumbnail for the given InstitutionId and DocumentId.
     *
     * @param string $institutionID   The institution ID
     * @param string $localDocumentID The Local document ID
     * @param string $username        The (optional) username for API invocation
     * @param string $password        The (optional) password for API invocation
     *
     * @return null|Response
     */
    protected function doGetThumbnail($institutionID, $localDocumentID, $username = null, $password = null)
    {
        $client = static::createClient();

        $extra = ['CONTENT_TYPE' => 'application/json'];
        if ($username) {
            $extra['PHP_AUTH_USER'] = $username;
        }
        if ($password) {
            $extra['PHP_AUTH_PW'] = $password;
        }

        $client->request(
            'GET',
            'thumbnails/'.$institutionID.'/'.$localDocumentID,
            [],
            [],
            $extra
        );

        return $client->getResponse();
    }

    /**
     * Posts a document to the ThumbnailAPI and returns the API response.
     *
     * @param string $contents The contents of the document to uploaded
     * @param string $fileName The name of the document to uploaded
     * @param string $fileMime The file Mime
     * @param string $username The (optional) username for API invocation
     * @param string $password The (optional) password for API invocation
     *
     * @return null|Response
     */
    protected function doPostThumbnail($contents, $fileName, $fileMime, $username = null, $password = null)
    {
        $client = static::createClient();

        $data = [
            'fileName' => $fileName,
            'fileData' => $contents,
            'fileMime' => $fileMime,
        ];

        $extra = [
            'CONTENT_TYPE' => 'application/json',
        ];

        if ($username) {
            $extra['PHP_AUTH_USER'] = $username;
        }
        if ($password) {
            $extra['PHP_AUTH_PW'] = $password;
        }

        $client->request('POST', 'thumbnails/', [], [], $extra, json_encode($data));

        return $client->getResponse();
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
