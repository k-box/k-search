<?php

namespace App\Tests\Security\Provider;

use App\Entity\ApiUser;
use App\Exception\KRegistryException;
use App\Security\Authorization\Voter\DataVoter;
use App\Security\Provider\KLinkRegistryUserProvider;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;
use OneOffTech\KLinkRegistryClient\ApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class KLinkRegistryUserProviderTest extends TestCase
{
    protected const APP_SECRET = 'eae63d14a41616f003df4cf5a73e7001';
    protected const APP_URL = 'https://example.url/test';
    protected const APP_NAME = 'App Name';
    protected const APP_ID = 12345;
    protected const APP_EMAIL = 'email@example.ext';

    /** @var KLinkRegistryUserProvider */
    private $provider;

    /** @var MockClient */
    private $mockClient;

    /** @var ApiClient */
    private $client;

    protected function setUp()
    {
        $this->mockClient = new MockClient();
        $this->client = new ApiClient($this->mockClient);

        $this->provider = new KLinkRegistryUserProvider(
            $this->client,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function loadUserFromApplicationUrlAndSecretRolesDataProvider(): array
    {
        return [
            'none' => [[], []],
            'data-add' => [[DataVoter::PERMISSION_ADD], [DataVoter::ROLE_DATA_ADD]],
            'data-edit' => [[DataVoter::PERMISSION_EDIT], [DataVoter::ROLE_DATA_EDIT]],
            'data-search' => [[DataVoter::PERMISSION_SEARCH], [DataVoter::ROLE_DATA_SEARCH]],
            'data-view' => [[DataVoter::PERMISSION_VIEW], [DataVoter::ROLE_DATA_VIEW]],
            'data-remove-own' => [[DataVoter::PERMISSION_REMOVE_OWN], [DataVoter::ROLE_DATA_REMOVE_OWN]],
            'data-remove-all' => [[DataVoter::PERMISSION_REMOVE_ALL], [DataVoter::ROLE_DATA_REMOVE_ALL]],

            // Mixed permissions
            'data-search-and-view' => [
                [DataVoter::PERMISSION_SEARCH, DataVoter::PERMISSION_VIEW],
                [DataVoter::ROLE_DATA_SEARCH, DataVoter::ROLE_DATA_VIEW],
            ],
            'data-remove-own-add-and-search' => [
                [DataVoter::PERMISSION_REMOVE_OWN, DataVoter::PERMISSION_ADD, DataVoter::PERMISSION_SEARCH],
                [DataVoter::ROLE_DATA_REMOVE_OWN, DataVoter::ROLE_DATA_ADD, DataVoter::ROLE_DATA_SEARCH],
            ],
        ];
    }

    /**
     * @param array $permissions
     * @param array $roles
     *
     * @dataProvider loadUserFromApplicationUrlAndSecretRolesDataProvider
     *
     * @throws \App\Exception\KRegistryException
     */
    public function testLoadUserFromApplicationUrlAndSecret(array $permissions, array $roles)
    {
        $application = $this->buildRegistryApplicationResponseData($permissions);
        $response = $this->mockRegistryResponse($application);
        $this->mockClient->addResponse($response);

        $user = $this->provider->loadUserFromApplicationUrlAndSecret('http://example.com', 'secret');

        $this->assertInstanceOf(ApiUser::class, $user);
        $this->assertSame($roles, $user->getRoles());
    }

    public function testVerificationFailsOnKRegistry()
    {
        $response = $this->mockRegistryResponse([], 400);
        $this->mockClient->addResponse($response);

        $this->expectException(BadCredentialsException::class);
        $this->provider->loadUserFromApplicationUrlAndSecret('http://example.com', 'secret');
    }

    public function testVerificationFailsWithClientException()
    {
        $this->mockClient->addException(new \RuntimeException());

        $this->expectException(KRegistryException::class);
        $this->provider->loadUserFromApplicationUrlAndSecret('http://example.com', 'secret');
    }

    public function testVerificationFailsOnKRegistryWithError()
    {
        $response = $this->mockRegistryResponse([
            'id' => 1234,
            'error' => 'Error',
        ], 200);
        $this->mockClient->addResponse($response);

        $this->expectException(KRegistryException::class);
        $this->provider->loadUserFromApplicationUrlAndSecret('http://example.com', 'secret');
    }

    private function buildRegistryApplicationResponseData(array $permissions = []): array
    {
        return [
            'id' => 1234,
            'result' => [
                'email' => self::APP_EMAIL,
                'name' => self::APP_NAME,
                'app_url' => self::APP_URL,
                'app_id' => self::APP_ID,
                'permissions' => $permissions,
            ],
        ];
    }

    private function mockRegistryResponse(array $data, int $status = 200): ResponseInterface
    {
        $response = new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        return $response;
    }
}
