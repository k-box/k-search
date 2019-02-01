<?php

namespace App\Tests\Service;

use App\Service\KlinkService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\Exception\InvalidKlinkException;
use Symfony\Component\Security\Core\Security;
use OneOffTech\KLinkRegistryClient\Model\Klink;
use App\Entity\ApiUser;
use App\Security\Authorization\Voter\DataVoter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class KlinkServiceTest extends KernelTestCase
{
    /**
     * @var KlinkService|MockObject
     */
    private $klinkService;
    
    private $tokenService;

    public function setUp()
    {
        self::bootKernel();

        // since the Security class if final and cannot be mocked
        // we mock the token storage and the token service
        // that are used by the Security class to
        // retrieve the current user

        $this->tokenService = $this->createMock(TokenInterface::class);
        
        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage->method('getToken')
            ->willReturn($this->tokenService);
            
        self::$container->set('security.token_storage', $tokenStorage);

        $this->klinkService = new KlinkService(
            new Security(static::$container),
            $this->createMock(LoggerInterface::class)
        );
    }


    private function createUser()
    {
        return new ApiUser(
            'local',
            'local@local',
            'local',
            null,
            DataVoter::ALL_ROLES,
            [
                Klink::createFromArray(['id' => '100', 'name' => 'Test K-Link']),
            ]);
    }


    public function testDefaultKlinkIdentifierReturn()
    {
        $this->tokenService->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createUser());
        
        $defaultKlink = $this->klinkService->getDefaultKlinkIdentifier();

        $this->assertSame('100', $defaultKlink);
    }
    
    public function testDefaultKlinkIdentifierThrowsIfKlinksAreNotConfiguredForApplication()
    {
        $user = new ApiUser('local','local@local','local',null,DataVoter::ALL_ROLES);

        $this->tokenService->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->expectException(InvalidKlinkException::class);
        
        $this->klinkService->getDefaultKlinkIdentifier();
    }
    
    public function testDefaultKlinkIdentifierThrowsIfMultipleKlinksAreConfiguredForApplication()
    {
        $user = new ApiUser('local','local@local','local',null,DataVoter::ALL_ROLES, [
            Klink::createFromArray(['id' => '100', 'name' => 'Test K-Link']),
            Klink::createFromArray(['id' => '101', 'name' => 'Test K-Link 1']),
        ]);

        $this->tokenService->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->expectException(InvalidKlinkException::class);
        
        $this->klinkService->getDefaultKlinkIdentifier();
    }

    public function testEnsureValidKlinksReturn()
    {
        $this->tokenService
            ->method('getUser')
            ->willReturn($this->createUser());
        
        $valid = $this->klinkService->ensureValidKlinks(['100']);

        $this->assertSame(['100'], $valid);
    }
    
    public function testEnsureValidKlinksUseDefaultValue()
    {
        $this->tokenService
            ->method('getUser')
            ->willReturn($this->createUser());
        
        $valid = $this->klinkService->ensureValidKlinks([]);

        $this->assertSame(['100'], $valid);
    }

    public function testEnsureValidKlinksSupportsKlinkClass()
    {
        $this->tokenService
            ->method('getUser')
            ->willReturn($this->createUser());

        $klink = Klink::createFromArray(['id' => '100', 'name' => 'Test']);
        
        $valid = $this->klinkService->ensureValidKlinks([$klink]);

        $this->assertSame([$klink], $valid);
    }
    
    public function testEnsureValidKlinksThrowsIfApplicationCannotPublish()
    {
        $user = new ApiUser('local','local@local','local',null,DataVoter::ALL_ROLES);

        $this->tokenService
            ->method('getUser')
            ->willReturn($user);

        $this->expectException(InvalidKlinkException::class);
        
        $this->klinkService->ensureValidKlinks(['100']);
    }
    
    public function testEnsureValidKlinksThrows()
    {
        $this->tokenService
            ->method('getUser')
            ->willReturn($this->createUser());

        $this->expectException(InvalidKlinkException::class);
        
        $this->klinkService->ensureValidKlinks(['110']);
    }



    public function testGetKlinkReturn()
    {
        $expected_klink = Klink::createFromArray(['id' => '100', 'name' => 'Test K-Link']);
        $user = new ApiUser('local','local@local','local',null,DataVoter::ALL_ROLES, [$expected_klink]);

        $this->tokenService
            ->method('getUser')
            ->willReturn($user);
        
        $actual_klink = $this->klinkService->getKlink('100');

        $this->assertSame($expected_klink, $actual_klink);
    }


    public function testGetKlinkReturnNullIfNotFound()
    {
        $this->tokenService
            ->method('getUser')
            ->willReturn($this->createUser());
        
        $actual_klink = $this->klinkService->getKlink('110');

        $this->assertNull($actual_klink);
    }

    
}
