<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserManager extends BaseUserManager
{
    const DEFAULT_SORTING_FIELD = 'email';

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
    
    /**
     * {@inheritDoc}
     */
    public function updateUser(UserInterface $user, $andFlush = true)
    {
        if ($user instanceof User) {
            $user->updateAvatar();
        }

        if ($user->getIsOrganizer()) {
            $user->addRole('ROLE_ORGANIZER');
        }

        if ($user->getIsSuperAdmin()) {
            $user->addRole('ROLE_SUPER_ADMIN');
        }

        parent::updateUser($user, $andFlush);
    }

    public function getFindUserQuery($sort_by = self::DEFAULT_SORTING_FIELD) 
    {

        return $this
            ->repository
            ->createQueryBuilder('u')
            ->orderBy('u.'.$sort_by)
            ->getQuery();
        
    }
}

