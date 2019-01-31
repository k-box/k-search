<?php

namespace App\Tests\Controller;

use App\Entity\ApiUser;
use App\Security\Provider\KLinkRegistryUserProvider;
use App\Service\DataService;
use App\Service\KlinkService;
use App\Service\DataStatusService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use OneOffTech\KLinkRegistryClient\Model\Klink;

abstract class AbstractJsonRpcControllerTest extends WebTestCase
{
    protected const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    protected const DATA_UUID_INVALID = 'a1bc8fb0a96297264edd8d0dabf93536';
    protected const REQUEST_ID = 'a1b2c3d4e5f6';
    protected const RPC_METHOD = 'POST';
    protected const APP_SECRET = 'eae63d14a41616f003df4cf5a73e7001';
    protected const APP_URL = 'https://example.url/test';
    protected const APP_NAME = 'App Name';
    protected const APP_EMAIL = 'email@example.ext';

    /**
     * @var Client|null
     */
    protected $client;

    /**
     * @var string[]
     */
    protected $userRoles = [];

    public function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    /**
     * @return string[]
     */
    public function getUserRoles(): array
    {
        return $this->userRoles;
    }

    /**
     * @param string[] $userRoles
     */
    public function setUserRoles(array $userRoles)
    {
        $this->userRoles = $userRoles;
    }

    /**
     * @return KLinkRegistryUserProvider|MockObject
     */
    protected function setMockedKLinkRegistryUserProvider(?ApiUser $wantedUser = null)
    {
        $mocked = $this->createMock(KLinkRegistryUserProvider::class);

        $user = $wantedUser ?? new ApiUser(
            self::APP_NAME,
            self::APP_EMAIL,
            self::APP_URL,
            self::APP_SECRET,
            $this->getUserRoles(),
            [Klink::createFromArray(['id' => '1', 'name' => 'Test K-Link'])]
        );
        $mocked->expects($this->once())
            ->method('loadUserFromApplicationUrlAndSecret')
            ->willReturn($user)
        ;
        $this->client->getContainer()->set(KLinkRegistryUserProvider::class, $mocked);

        return $mocked;
    }

    /**
     * @return DataService|MockObject
     */
    protected function setMockedDataService()
    {
        $mocked = $this->createMock(DataService::class);
        $this->client->getContainer()->set(DataService::class, $mocked);

        return $mocked;
    }
    /**
     * @return KlinkService|MockObject
     */
    protected function setMockedKlinkService()
    {
        $mocked = $this->createMock(KlinkService::class);
        $this->client->getContainer()->set(KlinkService::class, $mocked);

        return $mocked;
    }

    /**
     * @return DataStatusService|MockObject
     */
    protected function setMockedDataStatusService()
    {
        $mocked = $this->createMock(DataStatusService::class);
        $this->client->getContainer()->set(DataStatusService::class, $mocked);

        return $mocked;
    }

    protected function sendRequest(string $method, string $url, array $headers = [], string $contents = null)
    {
        $this->client->request($method, $url, [], [], $headers, $contents);
    }

    protected function sendAuthenticatedRequest(string $method, string $url, string $contents)
    {
        $this->setMockedKLinkRegistryUserProvider();
        $headers = [
            'HTTP_Authorization' => 'Bearer '.self::APP_SECRET,
            'HTTP_Origin' => self::APP_URL,
        ];
        $this->sendRequest($method, $url, $headers, $contents);
    }

    protected function assertJsonRpcResultResponse(string $response, array $result, ?string $responseId)
    {
        $this->assertJson($response, 'Response should be a JSON data: '.$response);
        $data = json_decode($response, true);
        $this->assertArrayHasKey('result', $data, $response);
        $this->assertEquals($result, $data['result'], $response, 0.0, 10, true);

        if ($responseId) {
            $this->assertArrayHasKey('id', $data, $response);
            $this->assertSame($responseId, $data['id'], $response);
        }
    }

    protected function assertJsonRpcErrorResponse(string $actualJson, int $code, ?string $message = null, $errorData = null, ?string $responseId = null): void
    {
        $this->assertJson($actualJson, 'Response should be a JSON data: '.$actualJson);
        $rpcResponse = json_decode($actualJson, true);
        $this->assertArrayHasKey('error', $rpcResponse, $actualJson);

        $this->assertArrayHasKey('code', $rpcResponse['error'], $actualJson);
        $this->assertSame($code, $rpcResponse['error']['code'], $actualJson);

        if ($errorData) {
            $this->assertArrayHasKey('data', $rpcResponse['error'], $actualJson);
            $this->assertSame($errorData, $rpcResponse['error']['data'], $actualJson);
        }

        if ($message) {
            $this->assertArrayHasKey('message', $rpcResponse['error'], $actualJson);
            $this->assertSame($message, $rpcResponse['error']['message'], $actualJson);
        }

        if ($responseId) {
            $this->assertArrayHasKey('id', $rpcResponse, $actualJson);
            $this->assertSame($responseId, $rpcResponse['id'], $actualJson);
        }
    }
}
