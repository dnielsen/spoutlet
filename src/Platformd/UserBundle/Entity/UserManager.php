<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserManager extends BaseUserManager
{
    /**
     * {@inheritDoc}
     */
    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsernameOrEmail($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('No user with name or email "%s" was found.', $username));
        }

        return $user; 
    }
}

