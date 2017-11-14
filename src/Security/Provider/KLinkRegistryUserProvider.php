<?php

namespace App\Security\Provider;

use App\Entity\ApiUser;
use App\Exception\KRegistryException;
use OneOffTech\KLinkRegistryClient\Client;
use OneOffTech\KLinkRegistryClient\Exception\ApplicationVerificationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KLinkRegistryUserProvider implements UserProviderInterface
{
    /**
     * @var Client
     */
    private $registryClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Client $registryClient, LoggerInterface $logger)
    {
        $this->registryClient = $registryClient;
        $this->logger = $logger;
    }

    public function loadUserFromApplicationUrlAndSecret(string $appUrl, string $appSecret)
    {
        $this->logger->info('Querying for application', [
            'app-url' => $appUrl,
            'app-secret' => $appSecret,
        ]);

        try {
            $application = $this->registryClient->access()->getApplication($appSecret, $appUrl);

            $this->logger->info(
                'Application found: id={id}, name="{name}"',
                [
                    'id' => $application->getAppId(),
                    'name' => $application->getName(),
                    'email' => $application->getEmail(),
                    'permissions' => $application->getPermissions(),
                ]
            );

            return new ApiUser(
                $application->getName(),
                $application->getEmail(),
                $application->getAppUrl(),
                $appSecret,
                $application->getPermissions()
            );
        } catch (ApplicationVerificationException $e) {
            throw new BadCredentialsException();
        } catch (\Exception $e) {
            throw new KRegistryException('Error communicating with the K-Link Registry.', $e->getCode(), $e);
        }
    }

    public function loadUserByUsername($username)
    {
        throw new \RuntimeException(sprintf('Wrong invocation! Method "%s" is not supported.', __METHOD__));
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof ApiUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return ApiUser::class === $class;
    }
}
