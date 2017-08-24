<?php

namespace App\Tests;

use App\Controller\DataController;
use App\Service\DataService;
use App\Tests\Helper\ModelHelper;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataControllerTest extends KernelTestCase
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

        $deleteRequest = [
            'id' => $sampleRequestId,
            'params' => [
                'uuid' => $sampleUUID,
            ],
        ];
        $requestContent = json_encode($deleteRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController($dataService);

        $expectedResponse = [
            'result' => [
                'code' => 200,
                'status' => 'Ok',
            ],
            'id' => 'uniq_id',
        ];
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

        $deleteRequest = [
            'id' => $sampleRequestId,
            'params' => [
                'uuid' => $sampleUUID,
            ],
        ];
        $requestContent = json_encode($deleteRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController($dataService);

        $expectedResponse = [
            'result' => [
                'code' => 400,
                'status' => 'Error',
            ],
            'id' => 'uniq_id',
        ];
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

        $deleteRequest = [
            'id' => $sampleRequestId,
            'params' => [
                'uuid' => $sampleUUID,
            ],
        ];
        $requestContent = json_encode($deleteRequest);

        $request = $this->createRequest($requestContent);

        $response = $dataController->postDataDelete($request, self::API_VERSION);
    }

    public function testItGetsData()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleRequestId = 'uniq_id';

        $dataModel = \App\Tests\Helper\ModelHelper::createDataModel($sampleUUID);

        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataService->expects($this->once())
            ->method('getData')
            ->with($sampleUUID)
            ->willReturn($dataModel);

        $getRequest = [
            'id' => $sampleRequestId,
            'params' => [
                'uuid' => $sampleUUID,
            ],
        ];
        $requestContent = json_encode($getRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController($dataService);

        $expectedResponse = [
            'result' => ModelHelper::createDataArray($sampleUUID),
            'id' => $sampleRequestId,
        ];
        $serializedExpectedResponse = json_encode($expectedResponse);

        $response = $dataController->postDataGet($request, self::API_VERSION);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($serializedExpectedResponse, $response->getContent());
    }

    public function testItAddsData()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleRequestId = 'uniq_id';
        $sampleTextualContent = 'textual content to be indexed';

        $dataModel = \App\Tests\Helper\ModelHelper::createDataModel($sampleUUID);

        $addRequest = [
            'id' => $sampleRequestId,
            'params' => [
                'data' => ModelHelper::createDataArray($sampleUUID),
                'data_textual_contents' => $sampleTextualContent,
            ],
        ];
        $requestContent = json_encode($addRequest);

        $request = $this->createRequest($requestContent);

        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataService->expects($this->once())
            ->method('addData')
            ->with($dataModel, $sampleTextualContent)
            ->willReturn(true);

        $dataService->expects($this->once())
            ->method('getData')
            ->with($sampleUUID)
            ->willReturn($dataModel);

        $dataController = $this->createDataController($dataService);

        $response = $dataController->postDataAdd($request, self::API_VERSION);

        $expectedResponse = [
            'id' => $sampleRequestId,
            'result' => ModelHelper::createDataArray($sampleUUID),
        ];
        $serializedExpectedResponse = json_encode($expectedResponse);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($serializedExpectedResponse, $response->getContent());
    }

    public function testItGetTheDataStatus()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleRequestId = 'uniq_id';
        $sampleStatus = 'queued';

        $dataModel = \App\Tests\Helper\ModelHelper::createDataModel($sampleUUID);
        $dataModel->status = $sampleStatus;

        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataService->expects($this->once())
            ->method('getData')
            ->with($sampleUUID)
            ->willReturn($dataModel);

        $getRequest = [
            'id' => $sampleRequestId,
            'params' => [
                'uuid' => $sampleUUID,
            ],
        ];
        $requestContent = json_encode($getRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController($dataService);

        $expectedResponse = [
            'result' => [
                'status' => $sampleStatus,
            ],
            'id' => $sampleRequestId,
        ];
        $serializedExpectedResponse = json_encode($expectedResponse);

        $response = $dataController->postDataStatus($request, self::API_VERSION);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($serializedExpectedResponse, $response->getContent());
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
}
