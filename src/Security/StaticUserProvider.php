<?php

namespace App\Security;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Yaml\Yaml;

class StaticUserProvider implements UserProviderInterface
{
    /** @var string UserList */
    protected $kernelRoot;
    protected $kernelCacheDir;
    protected $clientListFile;
    protected $clients;

    /**
     * Default constructor.
     *
     * @param string $kernelRoot
     * @param string $clientListFile The configuration file of the Users (.yml file)
     * @param mixed  $kernelCacheDir
     */
    public function __construct($kernelRoot, $kernelCacheDir, $clientListFile)
    {
        $this->kernelRoot = $kernelRoot;
        $this->kernelCacheDir = $kernelCacheDir;
        $this->clientListFile = $clientListFile;
        $this->initUsers();
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        if (array_key_exists($username, $this->clients)) {
            $userData = $this->clients[$username];
            if (is_array($userData)) {
                $userData = (object) $userData;

                return new KLinkUser($username,
                    $userData->password,
                    null,
                    [$userData->role],
                    $userData->institutionId
                );
            }
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof KLinkUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return KLinkUser::class === $class;
    }

    protected function initUsers()
    {
        $cacheFile = $this->kernelCacheDir.DIRECTORY_SEPARATOR.$this->clientListFile;
        $configFile = $this->kernelRoot.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$this->clientListFile;

        $cache = new ConfigCache($cacheFile, true);
        if (!$cache->isFresh()) {
            $contents = Yaml::parse(file_get_contents($configFile));
            $cache->write(serialize($contents['klink-clients']));
        }

        $this->clients = unserialize(file_get_contents($cacheFile));
    }
}
