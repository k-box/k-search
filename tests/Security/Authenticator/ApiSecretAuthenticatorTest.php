<?php

namespace App\Tests\Security\Authenticator;

use App\Entity\ApiUser;
use App\Security\Authenticator\ApiSecretAuthenticator;
use App\Security\Authorization\Voter\DataVoter;
use App\Security\Provider\KLinkRegistryUserProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use OneOffTech\KLinkRegistryClient\Model\Klink;

class ApiSecretAuthenticatorTest extends KernelTestCase
{
    public function testGetUserDisabled()
    {
        $authenticator = new ApiSecretAuthenticator();

        $provider = $this->createMock(KLinkRegistryUserProvider::class);
        $credentials = [
            'app_url' => null,
            'app_secret' => null,
        ];

        $user = $authenticator->getUser($credentials, $provider);
        $this->assertInstanceOf(ApiUser::class, $user);

        $this->assertTrue($authenticator->checkCredentials($credentials, $user));
        $this->assertSame(DataVoter::ALL_ROLES, $user->getRoles());
    }

    public function testGetUser()
    {
        $authenticator = new ApiSecretAuthenticator(true);

        $provider = $this->createMock(KLinkRegistryUserProvider::class);
        $klinks = [
            Klink::createFromArray(['id' => '100', 'name' => 'Test K-Link'])
        ];
        $apiUser = new ApiUser('name', 'email', 'app_url', 'app_secret', ['ROLE_TEST'], $klinks);
        $provider->expects($this->once())
            ->method('loadUserFromApplicationUrlAndSecret')
            ->with('app_url', 'app_secret')
            ->willReturn($apiUser)
        ;

        $credentials = [
            'app_url' => 'app_url',
            'app_secret' => 'app_secret',
        ];
        $user = $authenticator->getUser($credentials, $provider);
        $this->assertInstanceOf(ApiUser::class, $user);
        $this->assertSame('app_secret', $user->getPassword());
        $this->assertSame('app_url', $user->getUsername());

        $this->assertTrue($authenticator->checkCredentials($credentials, $user));
        $this->assertSame(['ROLE_TEST'], $user->getRoles());
        $this->assertSame($klinks, $user->getKlinks());
    }

    public function testGetCredentialsDisabled()
    {
        $authenticator = new ApiSecretAuthenticator();

        $request = new Request();
        $credentials = $authenticator->getCredentials($request);
        $this->assertInternalType('array', $credentials);
        $this->assertArrayHasKey('app_url', $credentials);
        $this->assertNull($credentials['app_url']);
        $this->assertArrayHasKey('app_secret', $credentials);
        $this->assertNull($credentials['app_secret']);
    }

    public function testGetCredentialsFromRequestWithNoAuth()
    {
        $authenticator = new ApiSecretAuthenticator(true);

        $request = new Request();
        $credentials = $authenticator->getCredentials($request);
        $this->assertNull($credentials);
    }

    public function testGetCredentialsFromRequestNoSecret()
    {
        $authenticator = new ApiSecretAuthenticator(true);
        $request = $this->createRequestWithHeaders('Bearer ABCDEF', null);
        $credentials = $authenticator->getCredentials($request);
        $this->assertNull($credentials);
    }

    public function incorrectAuthDataProvider(): array
    {
        return [
            'null-bearer' => [null],
            'no-bearer' => ['Something '],
            'no-bearer-val' => ['Something ABCDEFG'],
            'empty-bearer' => ['Bearer '],
            'short-bearer' => ['Bearer ABCD'],
        ];
    }

    /**
     * @dataProvider incorrectAuthDataProvider
     */
    public function testGetCredentialsFromRequestIncorrectAuth(?string $auth)
    {
        $authenticator = new ApiSecretAuthenticator(true);
        $request = $this->createRequestWithHeaders($auth, 'origin');
        $credentials = $authenticator->getCredentials($request);
        $this->assertNull($credentials);
    }

    public function testGetCredentialsFromRequest()
    {
        $authenticator = new ApiSecretAuthenticator(true);
        $request = $this->createRequestWithHeaders('Bearer ABCDEF', 'origin');
        $credentials = $authenticator->getCredentials($request);

        $this->assertInternalType('array', $credentials);
        $this->assertArrayHasKey('app_url', $credentials);
        $this->assertSame('origin', $credentials['app_url']);
        $this->assertArrayHasKey('app_secret', $credentials);
        $this->assertSame('ABCDEF', $credentials['app_secret']);
    }

    private function createRequestWithHeaders(?string $auth, ?string $origin): Request
    {
        $headers = [
            'HTTP_Authorization' => $auth,
            'HTTP_Origin' => $origin,
        ];

        return new Request([], [], [], [], [], $headers);
    }
}
