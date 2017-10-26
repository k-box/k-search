<?php

namespace App\Tests\Security;

use App\Tests\Controller\AbstractJsonRpcControllerTest;
use App\Tests\Controller\DataControllerGetTest;
use App\Tests\Helper\ModelHelper;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationSkippingTest extends AbstractJsonRpcControllerTest
{
    public function testWithKlinkRegistryAuthenticationDisabled()
    {
        $this->setKlinkRegistrationMode(false);

        $data = ModelHelper::createDataModel(self::DATA_UUID);
        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('getData')
            ->with(self::DATA_UUID)
            ->willReturn($data);

        $statusRequest = $this->getUUIDRequest();
        $this->sendRequest(self::RPC_METHOD, DataControllerGetTest::DATA_GET_ENDPOINT, [], $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $result = ModelHelper::createDataArray(self::DATA_UUID);
        $this->assertJsonRpcResultResponse($response->getContent(), $result, self::REQUEST_ID);
    }

    /**
     * @group current
     */
    public function testWithKlinkRegistryAuthenticationEnabled()
    {
        $this->setKlinkRegistrationMode(true);

        $statusRequest = $this->getUUIDRequest();
        $this->sendRequest(self::RPC_METHOD, DataControllerGetTest::DATA_GET_ENDPOINT, [], $statusRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_UNAUTHORIZED, 'API Authentication Required');
    }

    protected function setKlinkRegistrationMode(bool $mode): void
    {
        $this->overrideKlinkRegistryUserProvider($mode);
        $this->overrideApiSecretAuthenticator($mode);
    }

    /**
     * @return string
     */
    private function getUUIDRequest(): string
    {
        $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => [
                'uuid' => self::DATA_UUID,
            ],
        ]);

        return $data;
    }

    private function overrideKlinkRegistryUserProvider(bool $mode)
    {
        $this->client->getContainer()->set(\App\Security\Provider\KLinkRegistryUserProvider::class, new \App\Security\Provider\KLinkRegistryUserProvider(
            $this->createMock(\OneOffTech\KLinkRegistryClient\Client::class),
            $mode,
            $this->createMock(\Psr\Log\LoggerInterface::class)
        ));
    }

    private function overrideApiSecretAuthenticator(bool $mode)
    {
        $this->client->getContainer()->set(\App\Security\Authenticator\ApiSecretAuthenticator::class, new \App\Security\Authenticator\ApiSecretAuthenticator($mode));
    }
}
