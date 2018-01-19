<?php

namespace App\Security\Authorization\Voter;

use App\Model\Data\Data;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class DataVoter extends Voter
{
    public const PERMISSION_ADD = 'data-add';
    public const PERMISSION_EDIT = 'data-edit';
    public const PERMISSION_SEARCH = 'data-search';
    public const PERMISSION_VIEW = 'data-view';
    // Pseudo permission
    public const PERMISSION_REMOVE = 'data-remove';
    public const PERMISSION_REMOVE_OWN = 'data-remove-own';
    public const PERMISSION_REMOVE_ALL = 'data-remove-all';

    public const ALL_PERMISSIONS = [
        self::PERMISSION_ADD,
        self::PERMISSION_EDIT,
        self::PERMISSION_REMOVE,
        self::PERMISSION_REMOVE_OWN,
        self::PERMISSION_REMOVE_ALL,
        self::PERMISSION_SEARCH,
        self::PERMISSION_VIEW,
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

    public const MAP_PERMISSION_TO_ROLE = [
        self::PERMISSION_ADD => self::ROLE_DATA_ADD,
        self::PERMISSION_EDIT => self::ROLE_DATA_EDIT,
        self::PERMISSION_REMOVE_OWN => self::ROLE_DATA_REMOVE_OWN,
        self::PERMISSION_REMOVE_ALL => self::ROLE_DATA_REMOVE_ALL,
        self::PERMISSION_SEARCH => self::ROLE_DATA_SEARCH,
        self::PERMISSION_VIEW => self::ROLE_DATA_VIEW,
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
            case self::PERMISSION_ADD:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_ADD]);
            case self::PERMISSION_EDIT:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_EDIT]);
            case self::PERMISSION_SEARCH:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_SEARCH]);
            case self::PERMISSION_VIEW:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_VIEW]);
            case self::PERMISSION_REMOVE_ALL:
                return $this->decisionManager->decide($token, [self::ROLE_DATA_REMOVE_ALL]);
            // Handle the remove as remove_own
            case self::PERMISSION_REMOVE:
            case self::PERMISSION_REMOVE_OWN:
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
