<?php

namespace App\Tests\Controller;

use App\Security\Authorization\Voter\DataVoter;
use Symfony\Component\HttpFoundation\Response;

class DataSearchControllerTest extends AbstractJsonRpcControllerTest
{
    const SEARCH_ENDPOINT = '/api/3.7/data.search';

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
     */
    public function testDataSearchWithoutPermissionFails(array $roles)
    {
        $this->setUserRoles($roles);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('searchData');

        $searchRequest = $this->getSearchRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::SEARCH_ENDPOINT, $searchRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.');
    }
    
    public function testDataSearchWithInvalidKlinkFails()
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_SEARCH]);

        $searchRequest = $this->getSearchRequestData([
            "search" => "*",
            "filters" => "",
            "klinks" => "500",
            "aggregations" => null,
            "sort" => [],
            "limit" => 10,
            "offset" => 0
        ]);
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::SEARCH_ENDPOINT, $searchRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $failures = [
            'params.klinks' => 'Some K-Links are invalid',
        ];
        $this->assertJsonRpcErrorResponse($response->getContent(), 400, 'Wrong data provided!', $failures);
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
