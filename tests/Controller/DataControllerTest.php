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

    public function testItGetsData()
    {
        $sampleUUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $sampleRequestId = 'uniq_id';

        $dataService = $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataService->expects($this->once())
            ->method('getData')
            ->with($sampleUUID)
            ->willReturn($this->createDataModel($sampleUUID));

        $dataController = $this->createDataController($dataService);

        $getRequest = $this->createGetRequest($sampleRequestId, $sampleUUID);

        $requestContent = json_encode($getRequest);
        $request = $this->createRequest($requestContent);

        $expectedResponse = new \App\Model\Data\GetResponse($this->createDataModel($sampleUUID), $sampleRequestId);
        $serializedExpectedResponse = json_encode($expectedResponse);

        $response = $dataController->postDataGet($request, self::API_VERSION);

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

    private function createDataModel($sampleUUID): \App\Model\Data\Data
    {
        $data = new App\Model\Data\Data();
        $data->hash = md5('hash');
        $data->type = 'text/plain';
        $data->url = 'http://example.com/data.txt';
        $data->uuid = $sampleUUID;

        $data->copyright = new \App\Model\Data\Copyright();
        $data->copyright->owner = new \App\Model\Data\CopyrightOwner();
        $data->copyright->owner->name = 'KLink Organization';
        $data->copyright->owner->email = 'info@klink.asia';
        $data->copyright->owner->contact = 'KLink Website: http://www.klink.asia';

        $data->copyright->usage = new \App\Model\Data\CopyrightUsage();
        $data->copyright->usage->short = 'MPL-2.0';
        $data->copyright->usage->name = 'Mozilla Public License 2.0';
        $data->copyright->usage->reference = 'https://spdx.org/licenses/MPL-2.0.html';

        $data->properties = new \App\Model\Data\Properties();
        $data->properties->title = 'Adventures of Sherlock Holmes';
        $data->properties->filename = 'adventures-of-sherlock-holmes.pdf';
        $data->properties->mime_type = 'application/pdf';
        $data->properties->language = 'en';
        $data->properties->created_at = '2008-07-28T14:47:31Z';
        $data->properties->updated_at = '2008-07-28T14:47:31Z';
        $data->properties->size = '717590';
        $data->properties->abstract = 'It is a novel about a detective';
        $data->properties->thumbnail = 'https://ichef.bbci.co.uk/news/660/cpsprodpb/153B4/production/_89046968_89046967.jpg';

        return $data;
    }
}
