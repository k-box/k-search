<?php

namespace App\Tests;

use App\Controller\DataController;
use App\Service\DataService;
use App\Tests\Helper\ModelHelper;
use JMS\Serializer\SerializerInterface;
use Psr\Container\ContainerInterface;
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

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
        $this->validator = $this->container->get('validator');
        $this->serializer = $this->container->get('jms_serializer');
        $this->dataService = $this->createMock(DataService::class);
    }

    public function testItAddsData()
    {
        $this->markTestSkipped('This test must be rewritten');
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

        $dataModel->uploader->app_url = 'klink.test';

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

        $dataArray['uploader']['app_url'] = 'klink.test';

        $expectedResponse = [
            'id' => self::REQUEST_ID,
            'result' => $dataArray,
        ];

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
