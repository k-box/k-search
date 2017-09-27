<?php

namespace App\Tests;

use App\Controller\DataController;
use App\Exception\BadRequestException;
use App\Service\DataService;
use App\Tests\Helper\ModelHelper;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataControllerTest extends KernelTestCase
{
    private const API_VERSION = '0.0';
    private const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    private const REQUEST_ID = 'request-98765';

    /** @var DataService|\PHPUnit_Framework_MockObject_MockObject */
    private $dataService;

    /** @var ValidatorInterface */
    private $validator;

    /** @var SerializerInterface */
    private $serializer;

    public function setUp()
    {
        self::bootKernel();

        $container = self::$kernel->getContainer();
        $this->validator = $container->get('validator');
        $this->serializer = $container->get('jms_serializer');
        $this->dataService = $this->createMock(DataService::class);
    }

    public function testItDeletesData()
    {
        $this->dataService->expects($this->once())
            ->method('deleteData')
            ->with(self::DATA_UUID)
            ->willReturn(true);

        $deleteRequest = [
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID,
            ],
        ];
        $requestContent = json_encode($deleteRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController();

        $expectedResponse = [
            'result' => [
                'code' => 200,
                'status' => 'Ok',
            ],
            'id' => self::REQUEST_ID,
        ];

        $response = $dataController->postDataDelete($request, self::API_VERSION);
        $this->assertSameRPCResponse($expectedResponse, $response, 200);
    }

    public function testItDoesNotDeleteDataIfItDoesNotExist()
    {
        $this->dataService->expects($this->once())
            ->method('deleteData')
            ->with(self::DATA_UUID)
            ->willReturn(false);

        $deleteRequest = [
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID,
            ],
        ];
        $requestContent = json_encode($deleteRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController();

        $expectedResponse = [
            'result' => [
                'code' => 400,
                'status' => 'Error',
            ],
            'id' => self::REQUEST_ID,
        ];

        $response = $dataController->postDataDelete($request, self::API_VERSION);
        $this->assertSameRPCResponse($expectedResponse, $response, 200);
    }

    public function testItHandlesExceptions()
    {
        $this->expectException(BadRequestException::class);
        $sampleUUID = 'bad-uuid';

        $dataController = $this->createDataController();

        $deleteRequest = [
            'id' => self::REQUEST_ID,
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
        $dataModel = ModelHelper::createDataModel(self::DATA_UUID);

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($dataModel);

        $getRequest = [
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID,
            ],
        ];
        $requestContent = json_encode($getRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController();

        $expectedResponse = [
            'result' => ModelHelper::createDataArray(self::DATA_UUID),
            'id' => self::REQUEST_ID,
        ];

        $response = $dataController->postDataGet($request, self::API_VERSION);
        $this->assertSameRPCResponse($expectedResponse, $response, 200);
    }

    public function testItAddsData()
    {
        $sampleTextualContent = 'textual content to be indexed';

        $dataModel = ModelHelper::createDataModel(self::DATA_UUID);
        $dataArray = ModelHelper::createDataArray(self::DATA_UUID);

        $addRequest = [
            'id' => self::REQUEST_ID,
            'params' => [
                'data' => $dataArray,
                'data_textual_contents' => $sampleTextualContent,
            ],
        ];

        $this->dataService->expects($this->once())
            ->method('addData')
            ->with($dataModel, $sampleTextualContent)
            ->willReturn(true);

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($dataModel);

        $dataController = $this->createDataController();

        $request = $this->createRequest(json_encode($addRequest));

        $response = $dataController->postDataAdd($request, self::API_VERSION);

        $expectedResponse = [
            'id' => self::REQUEST_ID,
            'result' => $dataArray,
        ];

        $this->assertSameRPCResponse($expectedResponse, $response, 200);
    }

    public function testItGetTheDataStatus()
    {
        $sampleStatus = 'queued';

        $dataModel = ModelHelper::createDataModel(self::DATA_UUID);
        $dataModel->status = $sampleStatus;

        $this->dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($dataModel);

        $getRequest = [
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID,
            ],
        ];
        $requestContent = json_encode($getRequest);

        $request = $this->createRequest($requestContent);
        $dataController = $this->createDataController();

        $expectedResponse = [
            'result' => [
                'status' => $sampleStatus,
            ],
            'id' => self::REQUEST_ID,
        ];

        $response = $dataController->postDataStatus($request, self::API_VERSION);
        $this->assertSameRPCResponse($expectedResponse, $response, 200);
    }

    private function createDataController(): DataController
    {
        return new DataController($this->dataService, $this->validator, $this->serializer);
    }

    /**
     * @param $requestContent
     *
     * @return Request
     */
    private function createRequest($requestContent): Request
    {
        $request = new Request([], [], [], [], [], [], $requestContent);
        $request->setMethod('POST');

        return $request;
    }

    private function assertSameRPCResponse(array $expectedResponse, Response $response, ?int $httpStatus = null)
    {
        if (null !== $httpStatus) {
            $this->assertSame($httpStatus, $response->getStatusCode());
        }

        $this->assertJsonStringEqualsJsonString(json_encode($expectedResponse), $response->getContent());
    }
}
