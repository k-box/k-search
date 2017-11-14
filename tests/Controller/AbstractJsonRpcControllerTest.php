<?php

namespace App\Tests\Controller;

use App\Entity\ApiUser;
use App\Security\Provider\KLinkRegistryUserProvider;
use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
     * @var Client
     */
    protected $client = null;

    protected $userRoles = [];

    public function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    /**
     * @return array
     */
    public function getUserRoles(): array
    {
        return $this->userRoles;
    }

    /**
     * @param array $userRoles
     */
    public function setUserRoles(array $userRoles)
    {
        $this->userRoles = $userRoles;
    }

    /**
     * @return KLinkRegistryUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function setMockedKLinkRegistryUserProvider()
    {
        $mocked = $this->createMock(KLinkRegistryUserProvider::class);

        $user = new ApiUser(
            self::APP_NAME,
            self::APP_EMAIL,
            self::APP_URL,
            self::APP_SECRET,
            $this->getUserRoles()
        );
        $mocked->expects($this->once())
            ->method('loadUserFromApplicationUrlAndSecret')
            ->willReturn($user)
        ;
        $this->client->getContainer()->set(KLinkRegistryUserProvider::class, $mocked);

        return $mocked;
    }

    /**
     * @return DataService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function setMockedDataService()
    {
        $mocked = $this->createMock(DataService::class);
        $this->client->getContainer()->set(DataService::class, $mocked);

        return $mocked;
    }

    protected function sendRequest(string $method, string $url, array $headers = [], $contents = null)
    {
        $this->client->request($method, $url, [], [], $headers, $contents);
    }

    protected function sendAuthenticatedRequest(string $method, string $url, $contents)
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

    protected function assertJsonRpcErrorResponse(string $response, int $code, ?string $message = null, $errorData = null, ?string $responseId = null)
    {
        $this->assertJson($response, 'Response should be a JSON data: '.$response);
        $rpcResponse = json_decode($response, true);
        $this->assertArrayHasKey('error', $rpcResponse, $response);

        $this->assertArrayHasKey('code', $rpcResponse['error'], $response);
        $this->assertSame($code, $rpcResponse['error']['code'], $response);

        if ($errorData) {
            $this->assertArrayHasKey('data', $rpcResponse['error'], $response);
            $this->assertSame($errorData, $rpcResponse['error']['data'], $response);
        }

        if ($message) {
            $this->assertArrayHasKey('message', $rpcResponse['error'], $response);
            $this->assertSame($message, $rpcResponse['error']['message'], $response);
        }

        if ($responseId) {
            $this->assertArrayHasKey('id', $rpcResponse, $response);
            $this->assertSame($responseId, $rpcResponse['id'], $response);
        }
    }
}
