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

        $deleteRequest = $this->createDeleteRequest($sampleRequestId, $sampleUUID);

        $requestContent = json_encode($deleteRequest);
        $request = $this->createRequest($requestContent);

        $expectedResponse = new \App\Model\Status\StatusResponse(new Status(200, 'Ok'), $sampleRequestId);
        $serializedExpectedResponse = json_encode($expectedResponse);

        $response = $dataController->postDataDelete($request, self::API_VERSION);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($serializedExpectedResponse, $response->getContent());
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

        $requestContent = json_encode($deleteRequest);
        $request = $this->createRequest($requestContent);

        $expectedResponse = new \App\Model\Status\StatusResponse(new Status(400, 'Error'), $sampleRequestId);
        $serializedExpectedResponse = json_encode($expectedResponse);

        $response = $dataController->postDataDelete($request, self::API_VERSION);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($serializedExpectedResponse, $response->getContent());
    }

    public function testItHandlesExceptions()
    {
        $this->expectException(\App\Exception\BadRequestException::class);
        $sampleUUID = 'bad-uuid';
        $sampleRequestId = 'uniq_id';

        /** @var DataService $dataService */
        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataController = $this->createDataController($dataService);

        $deleteRequest = $this->createDeleteRequest($sampleRequestId, $sampleUUID);

        $requestContent = json_encode($deleteRequest);
        $request = $this->createRequest($requestContent);

        $response = $dataController->postDataDelete($request, self::API_VERSION);
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
