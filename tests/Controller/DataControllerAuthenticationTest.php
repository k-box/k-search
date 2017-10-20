<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class DataControllerAuthenticationTest extends AbstractJsonRpcControllerTest
{
    public function authenticatedApiEndpointsProvider(): array
    {
        return [
            ['POST', '/api/0.0/data.get'],
            ['POST', '/api/0.0/data.search'],
            ['POST', '/api/0.0/data.delete'],
            ['POST', '/api/0.0/data.add'],
            ['POST', '/api/0.0/data.status'],
        ];
    }

    /**
     * @dataProvider authenticatedApiEndpointsProvider
     *
     * @param string $method
     * @param string $url
     */
    public function testApiEndpointRequiresAuthentication(string $method, string $url)
    {
        $this->sendRequest($method, $url);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcError($response->getContent(), 401, 'API Authentication Required');
    }

    /**
     * @dataProvider authenticatedApiEndpointsProvider
     *
     * @param string $method
     * @param string $url
     */
    public function testApiEndpointRequiresCorrectCredentials(string $method, string $url)
    {
        $parameters = [
        ];

        $this->sendRequest($method, $url);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcError($response->getContent(), 401, 'API Authentication Required');
    }
}
