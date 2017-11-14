<?php

namespace App\Security\Authorization\Voter;

use App\Model\Data\Data;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class DataVoter extends Voter
{
    public const ADD = 'data-add';
    public const EDIT = 'data-edit';
    public const SEARCH = 'data-search';
    public const VIEW = 'data-view';
    // Pseudo permission
    public const REMOVE = 'data-remove';
    public const REMOVE_OWN = 'data-remove-own';
    public const REMOVE_ALL = 'data-remove-all';

    public const ALL_PERMISSIONS = [
        self::ADD,
        self::EDIT,
        self::REMOVE,
        self::REMOVE_OWN,
        self::REMOVE_ALL,
        self::SEARCH,
        self::VIEW,
    ];

    public const ROLE_DATA_ADD = 'ROLE_DATA_ADD';
    public const ROLE_DATA_EDIT = 'ROLE_DATA_EDIT';
    public const ROLE_DATA_SEARCH = 'ROLE_DATA_SEARCH';
    public const ROLE_DATA_VIEW = 'ROLE_DATA_VIEW';
    public const ROLE_DATA_REMOVE_ALL = 'ROLE_DATA_REMOVE_ALL';
    public const ROLE_DATA_REMOVE_OWN = 'ROLE_DATA_REMOVE_OWN';

    public const ALL_ROLES = [
        self::ROLE_DATA_ADD,
        self::ROLE_DATA_EDIT,
        self::ROLE_DATA_SEARCH,
        self::ROLE_DATA_VIEW,
        self::ROLE_DATA_REMOVE_ALL,
        self::ROLE_DATA_REMOVE_OWN,
    ];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ALL_PERMISSIONS, true)) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            // the user must be logged in; if not, deny access
            return false;
        }

        switch ($attribute) {
            case self::ADD:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_ADD]);
            case self::EDIT:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_EDIT]);
            case self::SEARCH:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_SEARCH]);
            case self::VIEW:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_VIEW]);
            case self::REMOVE_ALL:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_REMOVE_ALL]);
            // Handle the remove as remove_own
            case self::REMOVE:
            case self::REMOVE_OWN:
                if ($this->decisionManager->decide($token, [self::ROLE_DATA_REMOVE_ALL])) {
                    return true;
                }

                // If we have no data, check if the user has at least the permissions
                $removeOwnGranted = $this->decisionManager->decide($token, [self::ROLE_DATA_REMOVE_OWN]);
                if (!$subject instanceof Data) {
                    return $removeOwnGranted;
                }

                // Additionally we check if the user is also the uploader
                return $removeOwnGranted && $subject->uploader->appUrl === $user->getUsername();
            default:
                return false;
        }
    }
}
