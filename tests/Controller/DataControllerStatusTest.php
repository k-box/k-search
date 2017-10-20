<?php

namespace App\Tests\Controller;

use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\Data;
use App\Security\Authorization\Voter\DataVoter;
use App\Tests\Helper\ModelHelper;
use Symfony\Component\HttpFoundation\Response;

class DataControllerStatusTest extends AbstractJsonRpcControllerTest
{
    public const DATA_STATUS_ENDPOINT = '/api/0.0/data.status';

    public function testDataStatusFailsWithWrongDataUuid()
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('getData');

        $statusRequest = $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID_INVALID,
            ],
        ]);
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT, $statusRequest);

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

    public function testDataStatusWithStatusOk()
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);

        $data = ModelHelper::createDataModel(self::DATA_UUID);
        $data->status = 'Ok';

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $statusRequest = $this->getStatusRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $result = ['status' => 'Ok'];
        $this->assertJsonRpcResultResponse($response->getContent(), $result, self::REQUEST_ID);
    }

    public function testDataStatusWithNotExistingData()
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willThrowException(new SolrEntityNotFoundException('Not found'));

        $statusRequest = $this->getStatusRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_NOT_FOUND, 'Not found', null, self::REQUEST_ID);
    }

    public function provideFailingRoles(): array
    {
        return [
            'ROLE_DATA_ADD' => [[DataVoter::ROLE_DATA_ADD]],
            'ROLE_DATA_EDIT' => [[DataVoter::ROLE_DATA_EDIT]],
            'ROLE_DATA_SEARCH' => [[DataVoter::ROLE_DATA_SEARCH]],
            'ROLE_DATA_REMOVE_OWN' => [[DataVoter::ROLE_DATA_REMOVE_OWN]],
            'ROLE_DATA_REMOVE_ALL' => [[DataVoter::ROLE_DATA_REMOVE_ALL]],
        ];
    }

    /**
     * @dataProvider provideFailingRoles
     *
     * @param $roles
     */
    public function testDataStatusWithoutPermissionFails($roles)
    {
        $this->setUserRoles($roles);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('getData');

        $statusRequest = $this->getStatusRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.');
    }

    /**
     * @return string
     */
    private function getStatusRequestData(): string
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
