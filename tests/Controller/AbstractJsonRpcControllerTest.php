<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractJsonRpcControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client = null;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function sendRequest(string $method, string $url, array $headers = [], $contents = null)
    {
        $this->client->request($method, $url, [], [], $headers, $contents);
    }

    protected function assertJsonRpcError(string $response, int $code, ?string $message = null, ?int $responseId = null)
    {
        $this->assertJson($response, 'Response should be a JSON data');
        $data = json_decode($response, true);
        $this->assertArrayHasKey('error', $data, $data);

        $this->assertArrayHasKey('code', $data['error'], $data['error']);
        $this->assertSame($code, $data['error']['code']);

        if ($message) {
            $this->assertArrayHasKey('message', $data['error'], $data['error']);
            $this->assertSame($message, $data['error']['message']);
        }

        if ($responseId) {
            $this->assertArrayHasKey('id', $data);
            $this->assertSame($responseId, $data['id']);
        }
    }
}
