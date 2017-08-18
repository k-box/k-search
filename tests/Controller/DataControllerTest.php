<?php

namespace App\Tests;

use App\Controller\DataController;
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

        $serializedExpectedResponse = '{"result":{"code":200,"status":"Ok"},"id":"uniq_id"}';

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

        $serializedExpectedResponse = '{"result":{"code":400,"status":"Error"},"id":"uniq_id"}';

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

        $dataController = $this->createDataController($dataService);

        $getRequest = $this->createGetRequest($sampleRequestId, $sampleUUID);

        $requestContent = json_encode($getRequest);
        $request = $this->createRequest($requestContent);

        $serializedExpectedResponse = '{"result":{"uuid":"cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd","url":"http:\/\/example.com\/data.txt","hash":"30163935c002fc4e1200906c3d30a9c4956b4af9f6dcaef1eb4b1fcb8fba69e7a7acdc491ea5b1f2864ea8c01b01580ef09defc3b11b3f183cb21d236f7f1a6b","type":"document","copyright":{"owner":{"name":"KLink Organization","email":"info@klink.asia","contact":"KLink Website: http:\/\/www.klink.asia"},"usage":{"short":"MPL-2.0","name":"Mozilla Public License 2.0","reference":"https:\/\/spdx.org\/licenses\/MPL-2.0.html"}},"properties":{"title":"Adventures of Sherlock Holmes","filename":"adventures-of-sherlock-holmes.pdf","mime_type":"application\/pdf","language":"en","created_at":"2008-07-28T14:47:31Z","updated_at":"2008-07-28T14:47:31Z","size":"717590","abstract":"It is a novel about a detective","thumbnail":"https:\/\/ichef.bbci.co.uk\/news\/660\/cpsprodpb\/153B4\/production\/_89046968_89046967.jpg"}},"id":"uniq_id"}';

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

        $addRequest = $this->createAddRequest($sampleRequestId, $dataModel, $sampleTextualContent);
        $serializer = $this->getSerializer();
        $serializedAddRequest = $serializer->serialize($addRequest, 'json');

        $request = $this->createRequest($serializedAddRequest);

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

        $serializedExpectedResponse = '{"id":"uniq_id","result":{"uuid":"cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd","url":"http:\/\/example.com\/data.txt","hash":"30163935c002fc4e1200906c3d30a9c4956b4af9f6dcaef1eb4b1fcb8fba69e7a7acdc491ea5b1f2864ea8c01b01580ef09defc3b11b3f183cb21d236f7f1a6b","type":"document","copyright":{"owner":{"name":"KLink Organization","email":"info@klink.asia","contact":"KLink Website: http:\/\/www.klink.asia"},"usage":{"short":"MPL-2.0","name":"Mozilla Public License 2.0","reference":"https:\/\/spdx.org\/licenses\/MPL-2.0.html"}},"properties":{"title":"Adventures of Sherlock Holmes","filename":"adventures-of-sherlock-holmes.pdf","mime_type":"application\/pdf","language":"en","created_at":"2008-07-28T14:47:31Z","updated_at":"2008-07-28T14:47:31Z","size":717590,"abstract":"It is a novel about a detective","thumbnail":"https:\/\/ichef.bbci.co.uk\/news\/660\/cpsprodpb\/153B4\/production\/_89046968_89046967.jpg"}}}';

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

    private function createGetRequest($sampleRequestId, $sampleUUID): \App\Model\Data\GetRequest
    {
        $getRequest = new \App\Model\Data\GetRequest();
        $getRequest->id = $sampleRequestId;
        $getRequest->params = new \App\Model\Data\UUIDParam();
        $getRequest->params->uuid = $sampleUUID;

        return $getRequest;
    }

    private function createAddRequest($sampleRequestId, $dataModel, $sampleTextualContent): \App\Model\Data\AddRequest
    {
        $addRequest = new \App\Model\Data\AddRequest();
        $addRequest->id = $sampleRequestId;
        $addRequest->params = new \App\Model\Data\AddParams();
        $addRequest->params->data = $dataModel;
        $addRequest->params->dataTextualContents = $sampleTextualContent;

        return $addRequest;
    }
}
