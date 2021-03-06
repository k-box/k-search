<?php

namespace App\Tests\Controller;

use App\Exception\OutdatedDataRequestException;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Security\Authorization\Voter\DataVoter;
use App\Tests\Helper\TestModelHelper;
use Symfony\Component\HttpFoundation\Response;

class DataControllerAddTest extends AbstractJsonRpcControllerTest
{
    public const DATA_ADD_ENDPOINT = '/api/3.7/data.add';

    public function testDataAddWithMinimalDataSucceeded()
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);

        $data = TestModelHelper::buildDataModelMinimal();
        $data->uploader->appUrl = self::APP_URL;
        $data->uploader->email = null;
        $data->properties->updatedAt = new \DateTime();
        $data->status = DataStatus::STATUS_QUEUED_OK;

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $dataIn) use ($data) {
                $this->assertEquals($data->uuid, $dataIn->uuid);
                $this->assertEquals($data->uploader->appUrl, $dataIn->uploader->appUrl);
                $this->assertEquals($data->uploader->email, $dataIn->uploader->email);

                return true;
            }));

        $addData = file_get_contents(__DIR__.'/../fixtures/data-add.document-minimal.json');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDataAddToKlinkSucceeded()
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);

        $data = TestModelHelper::buildDataModelMinimal();
        $data->uploader->appUrl = self::APP_URL;
        $data->uploader->email = null;
        $data->properties->updatedAt = new \DateTime();
        $data->status = DataStatus::STATUS_QUEUED_OK;
        $data->klink_ids = ['1'];

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $dataIn) use ($data) {
                $this->assertEquals($data->uuid, $dataIn->uuid);
                $this->assertEquals($data->uploader->appUrl, $dataIn->uploader->appUrl);
                $this->assertEquals($data->uploader->email, $dataIn->uploader->email);
                $this->assertEquals($data->klink_ids, $dataIn->klink_ids);

                return true;
            }));

        $addData = file_get_contents(__DIR__.'/../fixtures/data-add.document-to-klink.json');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $returnedData = json_decode($response->getContent());
        $this->assertNotEmpty($returnedData->result->klinks);

        $returnedKlink = $returnedData->result->klinks[0];
        $this->assertEquals(1, $returnedKlink->id);
        $this->assertEquals('Test K-Link', $returnedKlink->name);
    }

    public function testDataAddToKlinkPreventedDueToInvalidKlink()
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);

        $data = TestModelHelper::buildDataModelMinimal();
        $data->uploader->appUrl = self::APP_URL;
        $data->uploader->email = null;
        $data->properties->updatedAt = new \DateTime();
        $data->status = DataStatus::STATUS_QUEUED_OK;
        $data->klink_ids = [3];

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('addData');

        $addData = file_get_contents(__DIR__.'/../fixtures/data-add.failing-document-to-klink.json');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $failures = [
            'klinks' => 'Some K-Links are invalid',
        ];
        $this->assertJsonRpcErrorResponse($response->getContent(), 400, 'Wrong data provided!', $failures);
    }

    public function testDataAddWithStrictRequiredDataSucceeded()
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);

        $data = TestModelHelper::buildDataModelStrict();
        $data->uploader->appUrl = self::APP_URL;
        $data->properties->updatedAt = new \DateTime();
        $data->status = DataStatus::STATUS_QUEUED_OK;

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $dataIn) use ($data) {
                $this->assertEquals($data->uuid, $dataIn->uuid);
                $this->assertEquals($data->uploader->appUrl, $dataIn->uploader->appUrl);

                return true;
            }));

        $addData = file_get_contents(__DIR__.'/../fixtures/data-add.document-strictly-required.json');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function provideFailureFiles(): array
    {
        return [
            'data' => [__DIR__.'/../fixtures/data-add.failing-00.json', [
                'params.data' => 'This value should not be blank.',
            ]],
            'min-data-level-1' => [__DIR__.'/../fixtures/data-add.failing-01.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties' => 'This value should not be blank.',
            ]],
            'min-data-level-2' => [__DIR__.'/../fixtures/data-add.failing-02.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties.title' => 'This value should not be blank.',
                'params.data.properties.filename' => 'This value should not be blank.',
                'params.data.properties.mime_type' => 'This value should not be blank.',
                'params.data.properties.language' => 'This value should not be blank.',
                'params.data.properties.created_at' => 'This value should not be blank.',
            ]],
            'min-data-level-3' => [__DIR__.'/../fixtures/data-add.failing-03.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties.title' => 'This value should not be blank.',
                'params.data.properties.filename' => 'This value should not be blank.',
                'params.data.properties.mime_type' => 'This value should not be blank.',
                'params.data.properties.language' => 'This value should not be blank.',
                'params.data.properties.created_at' => 'This value should not be blank.',
                'params.data.authors[0].name' => 'This value should not be blank.',
            ]],
            'min-data-level-4' => [__DIR__.'/../fixtures/data-add.failing-04.json', [
                'Invalid datetime "xxx", expected format Y-m-d\TH:i:s\Z.',
            ]],
            'min-data-level-4b' => [__DIR__.'/../fixtures/data-add.failing-04b.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties.language' => 'This value should have exactly 2 characters.',
                'params.data.copyright.owner.name' => 'This value should not be blank.',
                'params.data.copyright.usage.short' => 'This value should not be blank.',
                'params.data.copyright.usage.name' => 'This value should not be blank.',
            ]],
            'min-data-level-5' => [__DIR__.'/../fixtures/data-add.failing-05.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.copyright.owner.name' => 'This value should not be blank.',
                'params.data.copyright.usage.short' => 'This value should not be blank.',
                'params.data.copyright.usage.name' => 'This value should not be blank.',
            ]],
            'min-data-level-5b' => [__DIR__.'/../fixtures/data-add.failing-05b.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.copyright.owner.name' => 'This value should not be blank.',
            ]],
            'geo-location-invalid' => [__DIR__.'/../fixtures/data-add.failing-06-geo-location.json', [
                'params.data.geo_location' => 'Invalid GeoJson data: Syntax error',
            ]],
            'geo-location-invalid-type' => [__DIR__.'/../fixtures/data-add.failing-06-geo-location-01.json', [
                'params.data.geo_location' => 'Unsupported GeoJson type: Whatever',
            ]],
            'geo-location-invalid-point' => [__DIR__.'/../fixtures/data-add.failing-06-geo-location-02.json', [
                'params.data.geo_location' => 'Invalid GeoJson data: Coordinates of point #0 are incorrect',
            ]],
            'geo-location-invalid-polygon' => [__DIR__.'/../fixtures/data-add.failing-06-geo-location-03.json', [
                'params.data.geo_location' => 'Invalid GeoJson data: Polygon does not define a closed linear ring',
            ]],
            'geo-location-invalid-polygon2' => [__DIR__.'/../fixtures/data-add.failing-06-geo-location-04.json', [
                'params.data.geo_location' => 'Invalid GeoJson data: Polygon does not define a closed linear ring, first and last point MUT be identical',
            ]],
            'geo-location-invalid-polygon-with-holes' => [__DIR__.'/../fixtures/data-add.failing-06-geo-location-05.json', [
                'params.data.geo_location' => 'Invalid GeoJson data: Polygon with holes is not supported',
            ]],
        ];
    }

    /**
     * @dataProvider provideFailureFiles
     */
    public function testDataAddWithMissingRequiredFieldsFails(string $file, array $failures): void
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('addData');
        $dataService->expects($this->never())
            ->method('getData');

        $addData = file_get_contents($file);
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), 400, 'Wrong data provided!', $failures);
    }

    public function provideFailingRoles(): array
    {
        return [
            'ROLE_DATA_EDIT' => [[DataVoter::ROLE_DATA_EDIT]],
            'ROLE_DATA_REMOVE_OWN' => [[DataVoter::ROLE_DATA_REMOVE_OWN]],
            'ROLE_DATA_REMOVE_ALL' => [[DataVoter::ROLE_DATA_REMOVE_ALL]],
            'ROLE_DATA_SEARCH' => [[DataVoter::ROLE_DATA_SEARCH]],
            'ROLE_DATA_VIEW' => [[DataVoter::ROLE_DATA_VIEW]],
        ];
    }

    /**
     * @dataProvider provideFailingRoles
     */
    public function testDataAddWithoutAddPermissionFails(array $roles): void
    {
        $this->setUserRoles($roles);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('addData');
        $dataService->expects($this->never())
            ->method('deleteData');

        $addRequest = $this->getAddRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.');
    }

    public function testDataAddWithOutdatedDataFails(): void
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);
        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('addData')
            ->willThrowException(OutdatedDataRequestException::fromRequestId(self::REQUEST_ID));
        $dataService->expects($this->never())
            ->method('getData');

        $addData = file_get_contents(__DIR__.'/../fixtures/data-add.document-minimal.json');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            400,
            sprintf('Trying to add Data over a newer version of it (request: %s)', self::REQUEST_ID)
        );
    }

    private function getAddRequestData(array $params = []): string
    {
        $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => $params,
        ]);

        return $data;
    }
}
