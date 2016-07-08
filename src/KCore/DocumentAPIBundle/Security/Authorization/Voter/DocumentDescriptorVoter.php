<?php

namespace KCore\DocumentAPIBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use KCore\CoreBundle\Security\KLinkUser;

class DocumentDescriptorVoter implements VoterInterface
{
    const POST = 'post';
    const GET = 'get';
    const DELETE = 'delete';

    /**
     * @var VoterInterface
     */
    private $roleHierarchyVoter;

    private $institutionId;

    public function __construct($roleHierarchyVoter, $institutionId)
    {
        $this->roleHierarchyVoter = $roleHierarchyVoter;
        $this->institutionId = $institutionId;
    }

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array(
            self::POST,
            self::GET,
            self::DELETE,
        ));
    }

    public function supportsClass($class)
    {
        $supportedClass = 'KCore\CoreBundle\Entity\DocumentDescriptor';

        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * @var \KCore\CoreBundle\Entity\DocumentDescriptor $documentDescriptor
     **/
    public function vote(TokenInterface $token, $documentDescriptor, array $attributes)
    {
        // check if class of this object is supported by this voter
        if (!$this->supportsClass(get_class($documentDescriptor))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // check if the voter is used correct, only allow one attribute
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

        if ($this->roleHierarchyVoter->vote($token, null, array('ROLE_ADMIN')) == VoterInterface::ACCESS_GRANTED) {
            return VoterInterface::ACCESS_GRANTED;
        }

        $visibility = $documentDescriptor->getVisibility();
        $isOwnCore = $user->getInstitutionId() === $this->institutionId;
        $isOwnDocument = $user->getInstitutionId() === $documentDescriptor->getInstitutionId();

        if ($this->roleHierarchyVoter->vote($token, null, array('ROLE_LINK_DMS')) == VoterInterface::ACCESS_GRANTED) {
            $acl = $this->getDMSGrantedACL();

            foreach ($acl as $rule) {
                if ($rule['action'] === $method && $rule['visibility'] == $visibility) {
                    if (isset($rule['ownDocument']) && $rule['ownDocument'] != $isOwnDocument) {
                        return VoterInterface::ACCESS_DENIED;
                    }
                    if (isset($rule['ownCore']) && $rule['ownCore'] != $isOwnCore) {
                        return VoterInterface::ACCESS_DENIED;
                    }

                    return VoterInterface::ACCESS_GRANTED;
                }
            }
        }

        if ($this->roleHierarchyVoter->vote($token, null, array('ROLE_LINK_ADAPTER')) == VoterInterface::ACCESS_GRANTED) {
            $acl = $this->getAdapterGrantedACL();
            foreach ($acl as $rule) {
                if ($rule['action'] === $method && $rule['visibility'] == $visibility) {
                    if (isset($rule['ownDocument']) && $rule['ownDocument'] != $isOwnDocument) {
                        return VoterInterface::ACCESS_DENIED;
                    }
                    if (isset($rule['ownCore']) && $rule['ownCore'] != $isOwnCore) {
                        return VoterInterface::ACCESS_DENIED;
                    }

                    return VoterInterface::ACCESS_GRANTED;
                }
            }
        }

        // defaults to access denied
        return VoterInterface::ACCESS_DENIED;
    }

    private function getAdapterGrantedACL()
    {
        $public = \KCore\CoreBundle\Entity\DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC;

        // action and visibility are mandatory
        return array(
            array('action' => self::POST, 'visibility' => $public, 'ownDocument' => true),
            array('action' => self::GET, 'visibility' => $public),
            array('action' => self::DELETE, 'visibility' => $public, 'ownDocument' => true),
        );
    }

    private function getDMSGrantedACL()
    {
        $public = \KCore\CoreBundle\Entity\DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC;
        $private = \KCore\CoreBundle\Entity\DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE;

        // action and visibility are mandatory
        return array(
            array('action' => self::POST, 'visibility' => $public, 'ownDocument' => true),
            array('action' => self::POST, 'visibility' => $private, 'ownDocument' => true, 'ownCore' => true),

            array('action' => self::GET, 'visibility' => $public),
            array('action' => self::GET, 'visibility' => $private, 'ownDocument' => true, 'ownCore' => true),

            array('action' => self::DELETE, 'visibility' => $public, 'ownDocument' => true),
            array('action' => self::DELETE, 'visibility' => $private, 'ownDocument' => true, 'ownCore' => true),
        );
    }
}
