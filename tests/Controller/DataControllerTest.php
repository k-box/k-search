<?php

use App\Controller\DataController;
use App\Model\Status\Status;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataControllerTest extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    const API_VERSION = '0.0';

    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testItDeletesData()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleRequestId = 'uniq_id';

        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataService->expects($this->once())
            ->method('deleteData')
            ->with($sampleUUID)
            ->willReturn(true);

        $dataController = $this->createDataController($dataService);

        $serializer = $this->getSerializer();

        $deleteRequest = $this->createDeleteRequest($sampleRequestId, $sampleUUID);

        $requestContent = $serializer->serialize($deleteRequest, 'json');
        $request = $this->createRequest($requestContent);

        $expectedResponse = new \App\Model\Status\StatusResponse(new Status(200, 'Ok'), $sampleRequestId);
        $serializedExpectedResponse = $serializer->serialize($expectedResponse, 'json');

        $response = $dataController->postDataDelete($request, self::API_VERSION);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($serializedExpectedResponse, $response->getContent());
    }

    public function testItDoesNotDeleteDataIfItDoesNotExist()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleRequestId = 'uniq_id';

        /** @var DataService $dataService */
        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataService->expects($this->once())
            ->method('deleteData')
            ->with($sampleUUID)
            ->willReturn(false);

        $dataController = $this->createDataController($dataService);

        $deleteRequest = $this->createDeleteRequest($sampleRequestId, $sampleUUID);

        $serializer = $this->getSerializer();
        $requestContent = $serializer->serialize($deleteRequest, 'json');
        $request = $this->createRequest($requestContent);

        $expectedResponse = new \App\Model\Status\StatusResponse(new Status(400, 'Error'), $sampleRequestId);
        $serializedExpectedResponse = $serializer->serialize($expectedResponse, 'json');

        $response = $dataController->postDataDelete($request, self::API_VERSION);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($serializedExpectedResponse, $response->getContent());
    }

    public function testItHandlesExceptions()
    {
        $sampleUUID = 'bad-uuid';
        $sampleRequestId = 'uniq_id';

        /** @var DataService $dataService */
        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataController = $this->createDataController($dataService);

        $deleteRequest = $this->createDeleteRequest($sampleRequestId, $sampleUUID);

        $serializer = $this->getSerializer();
        $requestContent = $serializer->serialize($deleteRequest, 'json');
        $request = $this->createRequest($requestContent);

        $expectedResponse = new \App\Model\Error\ErrorResponse(new \App\Model\Error\Error(App\Model\Error\Error::INVALID_REQUEST, 'params.uuid: This is not a valid UUID.'), 'uniq_id');
        $serializedExpectedResponse = $serializer->serialize($expectedResponse, 'json');

        $response = $dataController->postDataDelete($request, self::API_VERSION);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($serializedExpectedResponse, $response->getContent());
    }

    public function xtestItCreatesADocument()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleRequestId = 'uniq_id';

        /** @var DataService $dataService */
        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataService->expects($this->once())
            ->method('createData')
            ->with($sampleUUID)
            ->willReturn(true);

        $dataController = $this->createDataController($dataService);
        $addRequest = $this->createAddRequest($sampleRequestId, $sampleUUID);

        $serializer = $this->getSerializer();
        $requestContent = $serializer->serialize($addRequest, 'json');
        $request = $this->createRequest($requestContent);
        $response = $dataController->postDataAdd($request, self::API_VERSION);

        $expectedResponseContent = new \App\Model\Data\AddResponse($addRequest->params, $sampleRequestId);
        $expectedResponse = new \Symfony\Component\HttpFoundation\JsonResponse($expectedResponseContent);

        $this->assertEquals($expectedResponse, $response);
    }

    protected static function getKernelClass()
    {
        return \App\Kernel::class;
    }

    /**
     * @return SerializerInterface
     */
    private function getSerializer(): SerializerInterface
    {
        return $this->container->get('jms_serializer');
    }

    /**
     * @return ValidatorInterface
     */
    private function getValidator(): ValidatorInterface
    {
        return $this->container->get('validator');
    }

    private function createDataController(DataService $dataService): DataController
    {
        $validator = $this->getValidator();
        $serializer = $this->getSerializer();

        return new DataController($dataService, $validator, $serializer);
    }

    /**
     * @param $requestContent
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function createRequest($requestContent): \Symfony\Component\HttpFoundation\Request
    {
        $request = new \Symfony\Component\HttpFoundation\Request([], [], [], [], [], [], $requestContent);
        $request->setMethod('POST');

        return $request;
    }

    /**
     * @param $sampleRequestId
     * @param $sampleUUID
     *
     * @return \App\Model\Data\DeleteRequest
     */
    private function createDeleteRequest($sampleRequestId, $sampleUUID): \App\Model\Data\DeleteRequest
    {
        $deleteRequest = new \App\Model\Data\DeleteRequest();
        $deleteRequest->id = $sampleRequestId;
        $deleteRequest->params = new \App\Model\Data\UUIDParam();
        $deleteRequest->params->uuid = $sampleUUID;

        return $deleteRequest;
    }

    private function createAddRequest($sampleRequestId, $sampleUUID)
    {
        $addRequest = new \App\Model\Data\AddRequest();
        $addRequest->id = $sampleRequestId;

        $addRequest->params = new App\Model\Data\Data();
        $addRequest->params->hash = md5('hash');
        $addRequest->params->type = 'text/plain';
        $addRequest->params->url = 'http://example.com/data.txt';
        $addRequest->params->uuid = $sampleUUID;
        $addRequest->params->copyright = new \App\Model\Data\Copyright();
        $addRequest->params->copyright->owner = new \App\Model\Data\CopyrightOwner();
        $addRequest->params->copyright->owner->contact = 'A';
        $addRequest->params->copyright->owner->email = 'a@a.a';
        $addRequest->params->copyright->owner->name = 'Mr. A';
        $addRequest->params->copyright->usage = new \App\Model\Data\CopyrightUsage();
        $addRequest->params->copyright->usage->name = 'Public Domain';
        $addRequest->params->copyright->usage->short = 'PD';

        return $addRequest;
    }
}
