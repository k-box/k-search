<?php

namespace App\Security;

use KLink\RegistryClient\RegistryClient;
use KLink\RegistryClient\RegistryClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class KLinkRegistryUserService
{
    /** @var CacheItemPoolInterface */
    private $userCache;

    /** @var RegistryClient */
    private $client;

    /** @var int */
    private $cacheLifetime;

    /**
     * @param RegistryClientInterface $registryClient
     * @param CacheItemPoolInterface  $cache
     * @param int                     $cacheLifetime
     */
    public function __construct(
        RegistryClientInterface $registryClient,
        CacheItemPoolInterface $cache,
        $cacheLifetime
    ) {
        $this->client = $registryClient;
        $this->userCache = $cache;
        $this->cacheLifetime = $cacheLifetime;
    }

    /**
     * @param string $appUrl
     *
     * @return KLinkUser
     */
    public function getUserByAppUrl($appUrl)
    {
        // Using our cache to avoid too many hits to the KRegistry APIs
        $cacheKey = $this->getAppUrlCacheKey($appUrl);
        $cached = $this->userCache->getItem($cacheKey);
        if ($cached->isHit()) {
            return $cached->get();
        }

        try {
            $application = $this->client->getApplicationByAppUrl($appUrl);
            $user = new KLinkUser(
                $application->getAppUrl(),
                $application->getAppSecret(),
                null,
                $application->getRoles(),
                $application->getId()
            );

            $cached->set($user);
            $cached->expiresAfter($this->cacheLifetime);
            $this->userCache->save($cached);

            return $user;
        } catch (\Exception $e) {
            throw new UsernameNotFoundException(
                sprintf('User "%s" does not exist.', $appUrl), 0, $e);
        }
    }

    /**
     * @param $appUrl
     *
     * @return string
     */
    private function getAppUrlCacheKey($appUrl)
    {
        return 'kregistry_app_'.md5($appUrl);
    }
}
