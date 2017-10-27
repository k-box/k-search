<?php

namespace App\Tests\Controller;

use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\Data;
use App\Security\Authorization\Voter\DataVoter;
use App\Tests\Helper\ModelHelper;
use Symfony\Component\HttpFoundation\Response;

class DataControllerDeleteTest extends AbstractJsonRpcControllerTest
{
    public const DATA_DELETE_ENDPOINT = '/api/0.0/data.delete';

    public function testDataDeleteFailsWithWrongDataUuid()
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('getData');
        $dataService->expects($this->never())
            ->method('deleteData');

        $deleteRequest = $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID_INVALID,
            ],
        ]);
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_DELETE_ENDPOINT, $deleteRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            Response::HTTP_BAD_REQUEST,
            'Wrong data provided!',
            [
                'params.uuid' => 'This is not a valid UUID.',
            ],
            self::REQUEST_ID
        );
    }

    public function testDataDeleteWithNotExistingData()
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willThrowException(new SolrEntityNotFoundException('Not found'));
        $dataService->expects($this->never())
            ->method('deleteData');

        $deleteRequest = $this->getDeleteRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_DELETE_ENDPOINT, $deleteRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_NOT_FOUND, 'Not found', null, self::REQUEST_ID);
    }

    public function testDataDeleteWithFailureOnDeletion()
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn(new Data());
        $dataService->expects($this->once())
            ->method('deleteData')
            ->with(self::DATA_UUID)
            ->willReturn(false);

        $deleteRequest = $this->getDeleteRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_DELETE_ENDPOINT, $deleteRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $result = ['code' => 500, 'status' => 'Error'];
        $this->assertJsonRpcResultResponse($response->getContent(), $result, self::REQUEST_ID);
    }

    public function testDataDeleteWithDeleteAllPermission()
    {
        $this->setUserRoles([
            DataVoter::ROLE_DATA_REMOVE_ALL,
        ]);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn(new Data());
        $dataService->expects($this->once())
            ->method('deleteData')
            ->with(self::DATA_UUID)
            ->willReturn(true);

        $deleteRequest = $this->getDeleteRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_DELETE_ENDPOINT, $deleteRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $result = ['code' => 200, 'status' => 'Ok'];
        $this->assertJsonRpcResultResponse($response->getContent(), $result, self::REQUEST_ID);
    }

    public function testDataDeleteWithDeleteOwnPermission()
    {
        $this->setUserRoles([
            DataVoter::ROLE_DATA_REMOVE_OWN,
        ]);

        $data = ModelHelper::createDataModel(self::DATA_UUID);
        $data->uploader->appUrl = self::APP_URL;

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);
        $dataService->expects($this->once())
            ->method('deleteData')
            ->with(self::DATA_UUID)
            ->willReturn(true);

        $deleteRequest = $this->getDeleteRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_DELETE_ENDPOINT, $deleteRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $result = ['code' => 200, 'status' => 'Ok'];
        $this->assertJsonRpcResultResponse($response->getContent(), $result, self::REQUEST_ID);
    }

    public function testDataDeleteWithDeleteOwnPermissionFails()
    {
        $this->setUserRoles([
            DataVoter::ROLE_DATA_REMOVE_OWN,
        ]);

        $data = ModelHelper::createDataModel(self::DATA_UUID);
        $data->uploader->appUrl = 'another-app-url';

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);
        $dataService->expects($this->never())
            ->method('deleteData');

        $deleteRequest = $this->getDeleteRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_DELETE_ENDPOINT, $deleteRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.', null, self::REQUEST_ID);
    }

    public function provideFailingRoles(): array
    {
        return [
            'ROLE_DATA_ADD' => [[DataVoter::ROLE_DATA_ADD]],
            'ROLE_DATA_EDIT' => [[DataVoter::ROLE_DATA_EDIT]],
            'ROLE_DATA_SEARCH' => [[DataVoter::ROLE_DATA_SEARCH]],
            'ROLE_DATA_VIEW' => [[DataVoter::ROLE_DATA_VIEW]],
        ];
    }

    /**
     * @dataProvider provideFailingRoles
     *
     * @param array $roles
     */
    public function testDataDeleteWithoutPermissionFails(array $roles)
    {
        $this->setUserRoles($roles);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('getData');
        $dataService->expects($this->never())
            ->method('deleteData');

        $deleteRequest = $this->getDeleteRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_DELETE_ENDPOINT, $deleteRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.');
    }

    private function getDeleteRequestData(): string
    {
        $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID,
            ],
        ]);

        return $data;
    }
}
