<?php

namespace App\Tests\Controller;

use App\Security\Authorization\Voter\DataVoter;
use Symfony\Component\HttpFoundation\Response;

class DataControllerAddTest extends AbstractJsonRpcControllerTest
{
    public const DATA_ADD_ENDPOINT = '/api/0.0/data.add';

    public function provideFailingRoles(): array
    {
        return [
            'ROLE_DATA_EDIT' => [[DataVoter::ROLE_DATA_EDIT]],
            'ROLE_DATA_REMOVE_OWN' => [[DataVoter::REMOVE_OWN]],
            'ROLE_DATA_REMOVE_ALL' => [[DataVoter::REMOVE_ALL]],
            'ROLE_DATA_SEARCH' => [[DataVoter::ROLE_DATA_SEARCH]],
            'ROLE_DATA_VIEW' => [[DataVoter::ROLE_DATA_VIEW]],
        ];
    }

    /**
     * @dataProvider provideFailingRoles
     *
     * @param array $roles
     */
    public function testDataAddWithoutAddPermissionFails(array $roles)
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

    private function getAddRequestData(array $params = []): string
    {
        $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => $params,
        ]);

        return $data;
    }
}
