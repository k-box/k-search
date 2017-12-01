<?php

namespace App\Security\Provider;

use App\Entity\ApiUser;
use App\Exception\KRegistryException;
use App\Security\Authorization\Voter\DataVoter;
use OneOffTech\KLinkRegistryClient\ApiClient;
use OneOffTech\KLinkRegistryClient\Exception\ApplicationVerificationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KLinkRegistryUserProvider implements UserProviderInterface
{
    /**
     * @var ApiClient
     */
    private $registryClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ApiClient $registryClient, LoggerInterface $logger)
    {
        $this->registryClient = $registryClient;
        $this->logger = $logger;
    }

    /**
     * Load an ApiUser from the KRegistry, matching the given appUrl and appSecret.
     *
     * @param string $appUrl
     * @param string $appSecret
     *
     * @throws BadCredentialsException
     * @throws KRegistryException
     *
     * @return ApiUser
     */
    public function loadUserFromApplicationUrlAndSecret(string $appUrl, string $appSecret): ApiUser
    {
        $this->logger->info('Querying for application', [
            'app-url' => $appUrl,
            'app-secret' => $appSecret,
        ]);

        try {
            $application = $this->registryClient->application()->getApplication($appSecret, $appUrl);

            $this->logger->info(
                'Application found: id={id}, name="{name}"',
                [
                    'id' => $application->getAppId(),
                    'name' => $application->getName(),
                    'email' => $application->getEmail(),
                    'permissions' => $application->getPermissions(),
                ]
            );

            $roles = $this->mapPermissionsToRoles($application->getPermissions());

            return new ApiUser(
                $application->getName(),
                $application->getEmail(),
                $application->getAppUrl(),
                $appSecret,
                $roles
            );
        } catch (ApplicationVerificationException $e) {
            throw new BadCredentialsException('Invalid credentials.', 0, $e);
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

    /**
     * Maps the list of permission from the KRegistry to the corresponding role.
     *
     * @param string[] $permissions
     *
     * @return string[]
     */
    private function mapPermissionsToRoles(array $permissions): array
    {
        $roles = [];
        foreach ($permissions as $permission) {
            if (array_key_exists($permission, DataVoter::MAP_PERMISSION_TO_ROLE)) {
                $roles[] = DataVoter::MAP_PERMISSION_TO_ROLE[$permission];
            }
        }

        return $roles;
    }
}
