<?php

namespace App\Tests\Controller;

use App\Exception\SolrEntityNotFoundException;
use App\Security\Authorization\Voter\DataVoter;
use OneOffTech\KLinkRegistryClient\Model\Klink as RegistryKlink;
use Symfony\Component\HttpFoundation\Response;

class KlinkListControllerTest extends AbstractJsonRpcControllerTest
{
    public const KLINK_LIST_ENDPOINT = '/api/3.7/klink.list';

    public function testKlinkListReturnApplicationKlinks()
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_VIEW]);

        $data = [
            RegistryKlink::createFromArray(['id' => '1', 'name' => 'Test K-Link']),
        ];

        $request = $this->getRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::KLINK_LIST_ENDPOINT, $request);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $result = $this->createResultsFromInput($data);

        $this->assertJsonRpcResultResponse($response->getContent(), $result, self::REQUEST_ID);
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
    public function testKlinkListWithoutPermissionFails(array $roles)
    {
        $this->setUserRoles($roles);

        $request = $this->getRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::KLINK_LIST_ENDPOINT, $request);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.');
    }

    private function createResultsFromInput($data)
    {
        $result = array_map(function ($k) {
            return [
                'id' => $k->getId(),
                'name' => $k->getName(),
            ];
        }, $data);

        return $result;
    }

    private function getRequestData(): string
    {
        $data = json_encode([
            'id' => self::REQUEST_ID,
        ]);

        return $data;
    }
}
