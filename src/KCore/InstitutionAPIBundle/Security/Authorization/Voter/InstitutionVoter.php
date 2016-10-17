<?php

namespace KCore\InstitutionAPIBundle\Security\Authorization\Voter;

use KCore\CoreBundle\Security\KLinkUser;
use KCore\InstitutionAPIBundle\Entity\InstitutionObjectForVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class InstitutionVoter implements VoterInterface
{
    const POST = 'post';
    const GET = 'get';
    const DELETE = 'delete';
    const GETALL = 'get-all';

    /** @var VoterInterface */
    private $roleHierarchyVoter;

    /** @var string */
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
        return in_array($attribute, [
            self::POST,
            self::GET,
            self::GETALL,
            self::DELETE,
        ]);
    }

    public function supportsClass($class)
    {
        $supportedClass = 'KCore\InstitutionAPIBundle\Entity\InstitutionObjectForVoter';

        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * @param TokenInterface            $token
     * @param InstitutionObjectForVoter $institution
     * @param array                     $attributes
     *
     * @return int
     */
    public function vote(TokenInterface $token, $institution, array $attributes)
    {
        // check if class of this object is supported by this voter
        if (!$this->supportsClass(get_class($institution))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // check if the voter is used correct, only allow two attribute
        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException(
                'Only one attribute is allowed for POST, GET or DELETE'
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

        if ($this->roleHierarchyVoter->vote($token, null, ['ROLE_ADMIN']) == VoterInterface::ACCESS_GRANTED) {
            return VoterInterface::ACCESS_GRANTED;
        }

        if (($this->roleHierarchyVoter->vote($token, null, ['ROLE_LINK_DMS']) == VoterInterface::ACCESS_GRANTED) ||
            ($this->roleHierarchyVoter->vote($token, null, ['ROLE_LINK_ADAPTER']) == VoterInterface::ACCESS_GRANTED)) {
            if ($institution->getInstitution()) {
                $isOwnInstitution = $user->getInstitutionId() === $institution->getInstitution()->getId();
            } else {
                $isOwnInstitution = false;
            }

            $acl = $this->getInstitutionACL();
            foreach ($acl as $rule) {
                if ($rule['action'] === $method) {
                    if (isset($rule['ownInstitution']) && $rule['ownInstitution'] != $isOwnInstitution) {
                        return VoterInterface::ACCESS_DENIED;
                    } else {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                }
            }
        }

        // defaults to access denied
        return VoterInterface::ACCESS_DENIED;
    }

    /**
     * @return array
     */
    protected function getInstitutionACL()
    {
        return [
            [
                'action' => self::GET,
            ],
            [
                'action' => self::GETALL,
            ],
            [
                'action' => self::POST, 'ownInstitution' => true,
            ],
        ];
    }
}
