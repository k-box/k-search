<?php

namespace KCore\SearchAPIBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use KCore\CoreBundle\Security\KLinkUser;
use KCore\SearchAPIBundle\SearchRequest\SearchObjectForVoter;
use KCore\CoreBundle\Entity\DocumentDescriptor;

class SearchVoter implements VoterInterface
{

    const SEARCH = 'search';

    /** @var VoterInterface */
    private $roleHierarchyVoter;

    /** @var  string */
    private $institutionId;

    /**
     * @param $roleHierarchyVoter
     * @param $institutionId
     */
    public function __construct($roleHierarchyVoter, $institutionId)
    {
        $this->roleHierarchyVoter = $roleHierarchyVoter;
        $this->institutionId = $institutionId;
    }

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array(
            self::SEARCH,
        ));
    }

    public function supportsClass($class)
    {
        $supportedClass = 'KCore\SearchAPIBundle\SearchRequest\SearchObjectForVoter';

        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * @param SearchObjectForVoter $searchObjectForVoter
     */
    public function vote(TokenInterface $token, $searchObjectForVoter, array $attributes)
    {
        // check if class of this object is supported by this voter
        if (!$this->supportsClass(get_class($searchObjectForVoter))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // check if the voter is used correct, only allow two attribute
        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException(
                'Only one attribute is allowed for SEARCH'
            );
        }

        // check if the given attribute is covered by this voter
        $method = $attributes[0];
        if (!$this->supportsAttribute($method)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // get current logged in user
        $user = $token->getUser();
        if (!$user instanceof KLinkUser) {
            return VoterInterface::ACCESS_DENIED;
        }

        if ($this->roleHierarchyVoter->vote($token, null, array('ROLE_ADMIN')) == VoterInterface::ACCESS_GRANTED) {
            return VoterInterface::ACCESS_GRANTED;
        }

        if ($this->roleHierarchyVoter->vote($token, null, array('ROLE_LINK_DMS')) == VoterInterface::ACCESS_GRANTED) {
            if ($searchObjectForVoter->getVisibility() === DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC) {
                return VoterInterface::ACCESS_GRANTED;
            } else {
                $isOwnCore = $user->getInstitutionId() === $this->institutionId;
                if ($searchObjectForVoter->getVisibility() === DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE && $isOwnCore) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }

            return VoterInterface::ACCESS_DENIED;
        }

        if ($this->roleHierarchyVoter->vote($token, null, array('ROLE_LINK_ADAPTER')) == VoterInterface::ACCESS_GRANTED) {
            if ($searchObjectForVoter->getVisibility() === DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        // defaults to access denied
        return VoterInterface::ACCESS_DENIED;
    }
}
