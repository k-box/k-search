<?php

namespace App\Tests\Controller;

use App\Security\Authorization\Voter\DataVoter;
use Symfony\Component\HttpFoundation\Response;

class DataControllerSearchTest extends AbstractJsonRpcControllerTest
{
    public function provideFailingRoles(): array
    {
        return [
            'ROLE_DATA_ADD' => [[DataVoter::ROLE_DATA_ADD]],
            'ROLE_DATA_EDIT' => [[DataVoter::ROLE_DATA_EDIT]],
            'ROLE_DATA_REMOVE_OWN' => [[DataVoter::ROLE_DATA_REMOVE_OWN]],
            'ROLE_DATA_REMOVE_ALL' => [[DataVoter::ROLE_DATA_REMOVE_ALL]],
            'ROLE_DATA_VIEW' => [[DataVoter::ROLE_DATA_VIEW]],
        ];
    }

    /**
     * @dataProvider provideFailingRoles
     *
     * @param array $roles
     */
    public function testDataSearchWithoutAddPermissionFails(array $roles)
    {
        $this->setUserRoles($roles);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('searchData');

        $searchRequest = $this->getSearchRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, '/api/3.0/data.search', $searchRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.');
    }

    private function getSearchRequestData(array $params = []): string
    {
        $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => $params,
        ]);

        return $data;
    }
}
