<?php

namespace App\Service;

use App\Exception\InvalidKlinkException;
use OneOffTech\KLinkRegistryClient\Model\Klink;
use Psr\Log\LoggerInterface;
use App\Entity\ApiUser;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class KlinkService
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $identifiers_cache = null;
    private $klinks_cache = null;

    public function __construct(
        Security $security,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->security = $security;
    }

    /**
     * Get the default K-Link identifier for the
     * current authenticated application.
     *
     * @throws InvalidKlinkException if there are zero K-Links registered to the application or there are more than one K-Link
     *
     * @return string the identifier of the default K-Link for the application
     */
    public function getDefaultKlinkIdentifier()
    {
        $klinks = $this->klinkIdentifiers();

        if (empty($klinks) || \count($klinks) > 1) {
            throw new InvalidKlinkException('A default K-Link cannot be selected, as the application do not explicity define one');
        }

        return (string) $klinks[0];
    }

    /**
     * Get the K-Link details that correspond to the specified identifier.
     *
     * @param string $identifier The K-Link identifier
     *
     * @return Klink|null the K-Link details, or null if the identifier does not correspond to a valid K-Link
     */
    public function getKlink(string $identifier)
    {
        // the application has a list of valid K-Links
        // we should only found the matching one
        $klinks = $this->klinks();

        return $klinks[$identifier] ?? null;
    }

    /**
     * @param array        $klinks  the identifiers of the K-Links to validate
     * @param string|Klink $default the default K-Link to return in case the $klinks array is empty
     *
     * @throws InvalidKlinkException if one of the specified K-Link is invalid
     *
     * @return array the filtered K-Links to return only the valid ones
     */
    public function ensureValidKlinks($klinks, $default = null)
    {
        $valid = $this->filterValidKlinks($klinks, $default ?? $this->getDefaultKlinkIdentifier());

        if (!empty($klinks) && \count($klinks) !== \count($valid)) {
            throw new InvalidKlinkException('Some K-Links are invalid');
        }

        return $valid;
    }

    /**
     * Filter an array of K-Links to return only the one
     * that the application can see.
     *
     * @param array        $klinks  the identifiers of the K-Links to filter
     * @param string|Klink $default the default K-Link to return in case the $klinks array is empty
     *
     * @return array the filtered K-Links to return only the valid ones
     */
    public function filterValidKlinks($klinks, $default)
    {
        if (empty($klinks)) {
            return [$default];
        }

        $valid_identifiers = $this->klinks();

        return array_filter($klinks, function ($k) use ($valid_identifiers) {
            $id = $k instanceof Klink ? $k->getId() : $k;

            return isset($valid_identifiers[$id]);
        });
    }

    /**
     * Get the current authenticated application.
     *
     * @return ApiUser|UserInterface|null
     */
    private function application()
    {
        return $this->security->getUser();
    }

    /**
     * Get current application K-Links.
     *
     * @return Klink[]
     */
    private function klinks()
    {
        if ($this->klinks_cache) {
            return $this->klinks_cache;
        }

        $app = $this->application();

        $klinks = $app && method_exists($app, 'getKlinks') ? $app->getKlinks() : [];

        if (empty($klinks)) {
            return $this->klinks_cache = $klinks;
        }

        $keys = array_map(function ($k) {
            return (string) $k->getId();
        }, $klinks);

        $this->klinks_cache = array_combine($keys, $klinks);

        return $this->klinks_cache;
    }

    /**
     * Get the valid K-Link identifiers from the current application.
     *
     * @return array
     */
    private function klinkIdentifiers()
    {
        if ($this->identifiers_cache) {
            return $this->identifiers_cache;
        }

        $this->identifiers_cache = array_keys($this->klinks());

        return $this->identifiers_cache;
    }
}
