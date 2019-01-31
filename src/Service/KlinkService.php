<?php

namespace App\Service;

use App\Exception\InvalidKlinkException;
use OneOffTech\KLinkRegistryClient\Model\Klink;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

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

    public function __construct(
        Security $security,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->security = $security;
    }

    public function someMethod()
    {
        // returns User object or null if not authenticated
        $user = $this->security->getUser();

        $this->logger->error('KlinkService', ['user' => $user]);
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
        $klinks = $this->klinks();

        if (empty($klinks) || \count($klinks) > 1) {
            throw new InvalidKlinkException('A default K-Link cannot be selected, as the application do not explicity define one');
        }

        return $klinks[0]->getId();
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

        $valid_identifiers = $this->klinkIdentifiers();

        return array_filter($klinks, function ($k) use ($valid_identifiers) {
            $id = $k instanceof Klink ? $k->getId() : $k;

            return \in_array($k, $valid_identifiers, true);
        });
    }

    /**
     * Get the current authenticated application.
     *
     * @return ApiUser
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
        $app = $this->application();

        return $app ? $app->getKlinks() : [];
    }

    /**
     * Get the valid K-Link identifier from the current application.
     *
     * @return array
     */
    private function klinkIdentifiers()
    {
        if ($this->identifiers_cache) {
            return $this->identifiers_cache;
        }

        $this->identifiers_cache = array_map(function ($k) {
            return $k->getId();
        }, $this->klinks());

        return $this->identifiers_cache;
    }
}
