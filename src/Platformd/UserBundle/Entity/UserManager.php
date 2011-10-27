<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserManager extends BaseUserManager
{
    const DEFAULT_SORTING_FIELD = 'email';

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsernameOrEmail($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('No user with name or email "%s" was found.', $username));
        }

        // don't let a user login if their locale doesn't match the current locale
        if ($user->getLocale() && $user->getLocale() != $this->getLocale()) {
            throw new UsernameNotFoundException(sprintf('The user "%s" cannot log into the locale "%s".', $username, $this->getLocale()));
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

    /**
     * Returns an empty user instance
     *
     * @return UserInterface
     */
    public function createUser()
    {
        $user = parent::createUser();

        $user->setLocale($this->getLocale());

        return $user;
    }

    /**
     * Set the container, because we need the locale (from the session) and
     * I don't want to risk something weird happening when I scope the user
     * manager to request.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function getLocale()
    {
        if (!$this->container) {
            return false;
        }

        return $this->container->get('session')->getLocale();
    }
}

