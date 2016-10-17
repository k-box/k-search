<?php

namespace KCore\DocumentAPIBundle\Security\Authorization\Voter;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Security\KLinkUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DocumentDescriptorVoter extends Voter
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

    /**
     * @return array
     */
    private function getAdapterGrantedACL()
    {
        $public = DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC;

        // action and visibility are mandatory
        return [
            ['action' => self::POST, 'visibility' => $public, 'ownDocument' => true],
            ['action' => self::GET, 'visibility' => $public],
            ['action' => self::DELETE, 'visibility' => $public, 'ownDocument' => true],
        ];
    }

    /**
     * @return array
     */
    private function getDMSGrantedACL()
    {
        $public = DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC;
        $private = DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE;

        // action and visibility are mandatory
        return [
            ['action' => self::POST, 'visibility' => $public, 'ownDocument' => true],
            ['action' => self::POST, 'visibility' => $private, 'ownDocument' => true, 'ownCore' => true],

            ['action' => self::GET, 'visibility' => $public],
            ['action' => self::GET, 'visibility' => $private, 'ownDocument' => true, 'ownCore' => true],

            ['action' => self::DELETE, 'visibility' => $public, 'ownDocument' => true],
            ['action' => self::DELETE, 'visibility' => $private, 'ownDocument' => true, 'ownCore' => true],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $subject instanceof DocumentDescriptor && in_array($attribute, [
            self::POST,
            self::GET,
            self::DELETE,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // get current logged in user
        $user = $token->getUser();
        if (!$user instanceof KLinkUser) {
            return false;
        }

        if ($this->roleHierarchyVoter->vote($token, null, ['ROLE_ADMIN']) == VoterInterface::ACCESS_GRANTED) {
            return true;
        }

        $visibility = $subject->getVisibility();
        $isOwnCore = $user->getInstitutionId() === $this->institutionId;
        $isOwnDocument = $user->getInstitutionId() === $subject->getInstitutionId();

        if ($this->roleHierarchyVoter->vote($token, null, ['ROLE_LINK_DMS']) == VoterInterface::ACCESS_GRANTED) {
            $acl = $this->getDMSGrantedACL();

            foreach ($acl as $rule) {
                if ($rule['action'] === $attribute && $rule['visibility'] == $visibility) {
                    if (isset($rule['ownDocument']) && $rule['ownDocument'] != $isOwnDocument) {
                        return false;
                    }
                    if (isset($rule['ownCore']) && $rule['ownCore'] != $isOwnCore) {
                        return false;
                    }

                    return true;
                }
            }
        }

        if ($this->roleHierarchyVoter->vote($token, null, ['ROLE_LINK_ADAPTER']) == VoterInterface::ACCESS_GRANTED) {
            $acl = $this->getAdapterGrantedACL();
            foreach ($acl as $rule) {
                if ($rule['action'] === $attribute && $rule['visibility'] == $visibility) {
                    if (isset($rule['ownDocument']) && $rule['ownDocument'] != $isOwnDocument) {
                        return false;
                    }
                    if (isset($rule['ownCore']) && $rule['ownCore'] != $isOwnCore) {
                        return false;
                    }

                    return true;
                }
            }
        }

        // defaults to access denied
        return false;
    }
}
