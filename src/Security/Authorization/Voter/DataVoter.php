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

        if (!$subject instanceof Data) {
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
                return $this->decisionManager->decide($token, ['ROLE_DATA_ADD']);
            case self::EDIT:
                return $this->decisionManager->decide($token, ['ROLE_DATA_EDIT']);
            case self::SEARCH:
                return $this->decisionManager->decide($token, ['ROLE_DATA_SEARCH']);
            case self::VIEW:
                return $this->decisionManager->decide($token, ['ROLE_DATA_VIEW']);
            case self::REMOVE_ALL:
                return $this->decisionManager->decide($token, ['ROLE_DATA_REMOVE_ALL']);
            // Handle the remove as remove_own
            case self::REMOVE:
            case self::REMOVE_OWN:
                if ($this->decisionManager->decide($token, ['ROLE_DATA_REMOVE_ALL'])) {
                    return true;
                }

                /* @var $subject Data */
                // Return true only if the user has the remove_own and it is the owner of the data
                return $this->decisionManager->decide($token, ['ROLE_DATA_REMOVE_OWN']) &&
                    $subject->uploader->appUrl === $user->getUsername();
            default:
                return false;
        }
    }
}
