<?php

namespace App\Security\Provider;

use App\Entity\ApiUser;
use App\Exception\KRegistryException;
use OneOffTech\KLinkRegistryClient\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KLinkRegistryUserProvider implements UserProviderInterface
{
    const ALL_ROLES = [
        'ROLE_DATA_ADD',
        'ROLE_DATA_EDIT',
        'ROLE_DATA_REMOVE_OWN',
        'ROLE_DATA_REMOVE_ALL',
        'ROLE_DATA_SEARCH',
        'ROLE_DATA_VIEW',
    ];
    /**
     * @var Client
     */
    private $registryClient;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Client $registryClient, bool $enabled, LoggerInterface $logger)
    {
        $this->registryClient = $registryClient;
        $this->enabled = $enabled;
        $this->logger = $logger;
    }

    public function loadUserFromApplicationUrlAndSecret(string $appUrl, string $appSecret)
    {
        $this->logger->info('Querying for application', [
            'app-url' => $appUrl,
            'app-secret' => $appSecret,
            'enabled' => $this->enabled,
        ]);

        if (!$this->enabled) {
            $this->logger->info('Building local ApiUser: K-Registry is disabled!');

            return new ApiUser('local', 'local@email.ext', $appUrl, $appSecret, self::ALL_ROLES);
        }

        try {
            // @todo: Remove the unnecessary $permissions array []
            $application = $this->registryClient->access()->getApplication($appSecret, $appUrl, []);

            $this->logger->info('Application found: id={id}, name={name}', [
                'id' => $application->getAppId(),
                'name' => $application->getName(),
                'permissions' => $application->getPermissions(),
            ]);

            return new ApiUser(
                $application->getName(),
                '',  // @todo: Use the email. when implemented on KRegistry $application->getEmail()
                $application->getAppUrl(),
                $appSecret,
                $application->getPermissions()
            );
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
