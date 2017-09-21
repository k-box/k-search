<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class ApiUser implements UserInterface
{
    /**
     * @var string
     */
    private $email;

    /** @var string */
    private $name;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string[] */
    private $roles;

    public function __construct(string $name, string $email, string $username, string $password, array $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles;
        $this->email = $email;
        $this->name = $name;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        // Nothing to do here.
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function eraseCredentials()
    {
        // Nothing to do here.
    }
}
