<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class DataControllerAuthenticationTest extends AbstractJsonRpcControllerTest
{
    public function authenticatedApiEndpointsProvider(): array
    {
        return [
            [self::RPC_METHOD, '/api/3.0/data.get'],
            [self::RPC_METHOD, '/api/3.0/data.search'],
            [self::RPC_METHOD, '/api/3.0/data.delete'],
            [self::RPC_METHOD, '/api/3.0/data.add'],
            [self::RPC_METHOD, '/api/3.0/data.status'],
        ];
    }

    /**
     * @dataProvider authenticatedApiEndpointsProvider
     */
    public function testApiEndpointRequiresAuthentication(string $method, string $url)
    {
        $this->sendRequest($method, $url);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            401,
            'API Authentication Required',
            'Authentication credentials could not be found.'
        );
    }

    /**
     * @dataProvider authenticatedApiEndpointsProvider
     */
    public function testApiEndpointRequiresAuthenticationWithOriginOnly(string $method, string $url)
    {
        $headers = [
            'HTTP_Origin' => 'https://some-url/ext ',
        ];

        $this->sendRequest($method, $url, $headers);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            401,
            'API Authentication Required',
            'Authentication credentials could not be found.'
        );
    }

    /**
     * @dataProvider authenticatedApiEndpointsProvider
     */
    public function testApiEndpointRequiresAuthenticationWithAuthOnly(string $method, string $url)
    {
        $headers = [
            'HTTP_Authorization' => 'Bearer wrong-token',
        ];

        $this->sendRequest($method, $url, $headers);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            401,
            'API Authentication Required',
            'Authentication credentials could not be found.'
        );
    }

    /**
     * @dataProvider authenticatedApiEndpointsProvider
     */
    public function testApiEndpointRequiresCorrectCredentials(string $method, string $url)
    {
        $registry = $this->setMockedKLinkRegistryUserProvider();
        $registry->expects($this->once())
            ->method('loadUserFromApplicationUrlAndSecret')
            ->willThrowException(new BadCredentialsException());

        $headers = [
            'HTTP_Authorization' => 'Bearer not-existing-token-for-app',
            'HTTP_Origin' => 'http://domain.app/app-url',
        ];

        $this->sendRequest($method, $url, $headers);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertJsonRpcErrorResponse(
            $response->getContent(),
            401,
            'Wrong API Authentication provided',
            'Invalid credentials.'
        );
    }
}
