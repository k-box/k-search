<?php

namespace App\Tests\Controller;

use App\Exception\ProcessingStatusNotFoundException;
use App\Exception\SolrEntityNotFoundException;
use App\Helper\DateHelper;
use App\Model\Data\DataStatus;
use App\Security\Authorization\Voter\DataVoter;
use Symfony\Component\HttpFoundation\Response;

class DataStatusControllerTest extends AbstractJsonRpcControllerTest
{
    public const DATA_STATUS_ENDPOINT = '/api/3.0/data.status';
    public const DATA_STATUS_ENDPOINT_V34 = '/api/3.4/data.status';

    public function testDataStatusFailsWithWrongDataUuid(): void
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);
        $this->setMockedDataService();

        $service = $this->setMockedDataStatusService();
        $service->expects($this->never())
            ->method('getDataStatus');

        $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID_INVALID,
            ],
        ]);
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT, $data);

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

    public function statusOkDataProvider(): iterable
    {
        yield [self::DATA_STATUS_ENDPOINT, [
            'status' => 'index.ok',
            'message' => 'Message ok',
        ]];
        yield [self::DATA_STATUS_ENDPOINT_V34, [
            'status' => 'index.ok',
            'message' => 'Message ok',
            'request_id' => self::REQUEST_ID,
            'request_received_at' => '2018-01-01T10:11:12Z',
        ]];
        yield [self::DATA_STATUS_ENDPOINT_V34, [
            'status' => 'index.ok',
            'message' => 'Message ok',
            'request_id' => self::REQUEST_ID,
            'request_received_at' => '2018-01-01T10:11:12Z',
        ], 'data'];
    }

    /**
     * @dataProvider statusOkDataProvider
     *
     * @param null|mixed $type
     */
    public function testDataStatusWithStatusOk(string $url, array $expectedResult, $type = null): void
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);
        $this->setMockedDataService();

        $statusRequest = $this->getStatusRequestData($type);

        $dataStatus = new DataStatus();
        $dataStatus->status = DataStatus::STATUS_INDEX_OK;
        $dataStatus->message = 'Message ok';
        $dataStatus->requestId = self::REQUEST_ID;
        $dataStatus->requestReceivedAt = new \DateTime('2018-01-01T10:11:12');

        $dataStatusService = $this->setMockedDataStatusService();
        $dataStatusService->expects($this->once())
            ->method('getDataStatus')
            ->willReturn($dataStatus);

        $this->sendAuthenticatedRequest(self::RPC_METHOD, $url, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcResultResponse($response->getContent(), $expectedResult, self::REQUEST_ID);
    }

    public function notExistingSolrDataProvider(): iterable
    {
        yield [self::DATA_STATUS_ENDPOINT];
        yield [self::DATA_STATUS_ENDPOINT_V34]; // Use default type
        yield [self::DATA_STATUS_ENDPOINT_V34, 'data'];
    }

    /**
     * @dataProvider notExistingSolrDataProvider
     */
    public function testDataStatusWithNotExistingDataOnSolrFails(string $url, string $type = null): void
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);
        $this->setMockedDataService();

        $dataStatusService = $this->setMockedDataStatusService();
        $dataStatusService->expects($this->once())
            ->method('getDataStatus')
            ->with('data', self::DATA_UUID)
            ->willThrowException(new SolrEntityNotFoundException('Not found'));

        $statusRequest = $this->getStatusRequestData($type);
        $this->sendAuthenticatedRequest(self::RPC_METHOD, $url, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            Response::HTTP_NOT_FOUND,
            'Not found',
            null,
            self::REQUEST_ID
        );
    }

    public function testDataStatusWithProcessingExceptionFails(): void
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);
        $this->setMockedDataService();

        $dataStatusService = $this->setMockedDataStatusService();
        $dataStatusService->expects($this->once())
            ->method('getDataStatus')
            ->with('processing', self::DATA_UUID)
            ->willThrowException(new ProcessingStatusNotFoundException('Not found'));

        $statusRequest = $this->getStatusRequestData('processing');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT_V34, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            Response::HTTP_NOT_FOUND,
            'Not found',
            null,
            self::REQUEST_ID
        );
    }

    public function testDataStatusWithInvalidTypeFails(): void
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);
        $this->setMockedDataService();

        $dataStatusService = $this->setMockedDataStatusService();
        $dataStatusService->expects($this->never())
            ->method('getDataStatus');

        $statusRequest = $this->getStatusRequestData('invalid');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT_V34, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            Response::HTTP_BAD_REQUEST,
            'Wrong data provided!',
            ['params.type' => 'The value you selected is not a valid choice.'],
            self::REQUEST_ID
        );
    }

    public function testDataStatusSucceeds(): void
    {
        $this->setUserRoles(DataVoter::ALL_ROLES);
        $this->setMockedDataService();

        $dataStatus = new DataStatus();
        $dataStatus->message = 'Message';
        $dataStatus->type = 'processing';
        $dataStatus->status = 'status.ok';
        $dataStatus->requestReceivedAt = DateHelper::createUtcDate('2018-04-22 10:11:23');

        $dataStatusService = $this->setMockedDataStatusService();
        $dataStatusService->expects($this->once())
            ->method('getDataStatus')
            ->with('processing', self::DATA_UUID)
            ->willReturn($dataStatus);

        $statusRequest = $this->getStatusRequestData('processing');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_STATUS_ENDPOINT_V34, $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertJsonRpcResultResponse($response->getContent(), [
            'message' => 'Message',
            'type' => 'processing',
            'status' => 'status.ok',
            'request_received_at' => DateHelper::formatDate($dataStatus->requestReceivedAt),
        ], self::REQUEST_ID);
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
     */
    public function testDataStatusWithoutPermissionFails(array $roles): void
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

    private function getStatusRequestData($type = null): string
    {
        $data = [
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID,
            ],
        ];

        if ($type) {
            $data['params']['type'] = $type;
        }

        return json_encode($data);
    }
}
