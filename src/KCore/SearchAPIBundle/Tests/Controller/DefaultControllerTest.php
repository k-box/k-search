<?php

namespace KCore\SearchAPIBundle\Tests\Controller;

use JMS\Serializer\Serializer;
use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Services\DocumentService;
use KCore\SearchAPIBundle\Entity\SearchResults;
use KCore\SearchAPIBundle\SearchRequest\SearchRequestParameters;
use Solarium\QueryType\Analysis\Query\Document;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class DefaultControllerTest extends WebTestCase
{
    /** @var Serializer $serializer */
    static $serializer;

    /** @var DocumentService */
    static $documentService;

    /** @var DocumentDescriptor[] */
    static $documentDescriptors;

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

        /** @var DocumentService $documentService */
        self::$documentService = $container->get('klink.document.service');

        self::$documentDescriptors = array();
        self::$documentDescriptors = array_merge(self::$documentDescriptors, self::initDocumentDescriptor(3, 'opossum'));
        self::$documentDescriptors = array_merge(self::$documentDescriptors, self::initDocumentDescriptor(3, 'ulysses odyssey'));

        foreach (self::$documentDescriptors as $key => $documentDescriptor) {
            self::$documentService->indexDocumentDescriptor($documentDescriptor);
        }
    }

    /**
     * @param int $total
     * @param $defaultContent
     * @return DocumentDescriptor[]
     */
    protected static function initDocumentDescriptor($total = 3, $defaultContent)
    {
        $docs = array();
        $faker =  \Faker\Factory::create();
        foreach (range(1, $total) as $i) {
            $institutionId = 'testInstitutionID';
            $docDesc = new DocumentDescriptor($institutionId, 'testDocumentID' . $faker->randomNumber());
            $docDesc->setVisibility(DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC);
            $docDesc->setDocumentURI($faker->url . '/document' . $i);
            $docDesc->setThumbnailURI($docDesc->getDocumentURI() . '/thumbnail');

            $docDesc->setCreationDate($faker->dateTimeThisMonth);
            $docDesc->setUserOwner($faker->firstName . ' Owner <' . $faker->safeEmail . '>');
            $docDesc->setUserUploader($faker->firstName . ' Uploader <' . $faker->safeEmail . '>');

            $docDesc->addAuthor($faker->firstName . ' Author0 <' . $faker->safeEmail . '>');
            $docDesc->addAuthor($faker->firstName . ' Author1 <' . $faker->companyEmail . '>');

            switch($i) {
                case 1:
                    $docDesc->setTitle($defaultContent);
                    break;
                case 2:
                    $docDesc->setContents($defaultContent);
                    break;
                case 3:
                    $docDesc->setAbstract($defaultContent);
                    break;
            }

            $docDesc->setDocumentType('Document');
            $docDesc->setMimeType('application/pdf');
            $docDesc->setHash($faker->sha256 . $faker->sha256);
            $docs[] = $docDesc;
        }
        return $docs;
    }

    public static function tearDownAfterClass() {
        foreach(self::$documentDescriptors as $documentDescriptor) {
            self::$documentService->deleteDocumentDescriptor($documentDescriptor);
        }
    }


    /**
     * @param array $parameters
     * @return Response|null
     */
    protected function doRequestSearch(array $parameters)
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/kcore/search/',
            $parameters,
            array(),
            array('CONTENT_TYPE' => 'application/json')
        );

        return $client->getResponse();
    }


    public function wrongParametersProvider() {
        return array(
            array(array('numStart' => 'aaa')),
            array(array('numResults' => 'aaa')),
            array(array('visibility' => '1234')),
            array(array('visibility' => 'qqqq')),
            array(array('numStart' => 'aaa', 'numResults' => 'aaa')),
            array(array('numStart' => 'aaa', 'numResults' => 'aaa', 'visibility' => 'aaa')),
            array(array('numStart' => 'aaa', 'visibility' => 'aaa')),
            array(array('numResults' => 'aaa', 'visibility' => 'aaa')),
        );
    }

    /**
     * @param $params
     * @dataProvider wrongParametersProvider
     */
    public function testSearchDocumentDescriptorWrongParameters($params) {
        $response = $this->doRequestSearch($params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * @return array
     */
    public function searchParametersProvider() {
        return array(

            // Empty Queries
            array(0, array()),
            // The Private search is disabled now!
            // array(0, array('startResult' => 7, 'numResults' => 3, 'visibility' => DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE)),
            array(0, array('startResult' => 7, 'numResults' => 3)),
            array(0, array('startResult' => 7, 'numResults' => 3, 'visibility' => DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC)),
            array(0, array('startResult' => 7, 'visibility' => DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC)),
            array(0, array('numResults' => 3, 'visibility' => DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC)),

            array(3, array('query' => 'opossum')),
            array(3, array('query' => 'ulysses')),
            array(6, array('query' => 'ulysses opossum')),
        );
    }

    /**
     * @dataProvider searchParametersProvider
     * @param $expectedResults
     * @param $params
     */
    public function testSearchDocumentDescriptor($expectedResults, $params)
    {
        $response = $this->doRequestSearch($params);
        $this->assertJsonResponse($response);

        $parameters = new SearchRequestParameters($params);

        /** @var SearchResults $searchResults */
        $searchResults = self::$serializer->deserialize($response->getContent(), 'KCore\SearchAPIBundle\Entity\SearchResults', 'json');
        $this->assertCorrectSearchResultsResponse($searchResults, $parameters);

        $this->assertEquals($expectedResults, $searchResults->getItemCount(), 'Search Failed, query=' . $parameters->getQuery());
    }

    /**
     * @param SearchResults $searchResults
     * @param $params
     */
    function assertCorrectSearchResultsResponse(SearchResults $searchResults, SearchRequestParameters $parameters) {
        $this->assertEquals($parameters->getQuery(), $searchResults->getQuery());
        $this->assertEquals($parameters->getNumResults(), $searchResults->getNumResults());
        $this->assertEquals($parameters->getStartResult(), $searchResults->getStartResult());
        $this->assertEquals($parameters->getVisibility(), $searchResults->getVisibility());
        $this->assertInternalType('integer', $searchResults->getQueryTime());
        $this->assertInternalType('integer', $searchResults->getItemCount());
        $this->assertEquals($searchResults->getItemCount(), count($searchResults->getItems()));
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
