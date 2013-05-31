<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;

use Gaufrette\Filesystem;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\ORM\AbstractQuery;

class UserManager extends BaseUserManager
{
    const DEFAULT_SORTING_FIELD = 'email';

    /**
     * @var \Gaufrette\Filesystem
     */
    protected $filesystem;

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

        /*
         * Removing this for now. With the big multi-level authentication
         * system with CEVO, we just need to allow everyone to login anywhere
         *
         // don't let a user login if their locale doesn't match the current locale
         if ($user->getLocale() && $user->getLocale() != $this->getLocale()) {
             throw new UsernameNotFoundException(sprintf('The user "%s" cannot log into the locale "%s".', $username, $this->getLocale()));
    }
         */

        return $user;
    }

    public function getFindUserQuery($search = null, $type='text', $sort_by = self::DEFAULT_SORTING_FIELD)
    {
        $qb = $this
            ->repository
            ->createQueryBuilder('u')
            ->orderBy('u.'.$sort_by)
        ;

        if ($search) {

            $where = $type == 'ip' ? 'u.ipAddress = :search' : 'u.username like :search OR u.email LIKE :search';
            $search = $type == 'ip' ? $search : '%'.$search.'%';

            $qb
                ->andWhere($where)
                ->setParameter('search', $search)
            ;
        }

        if ($this->getLocale()) {
            $qb
                ->andWhere('u.locale = :locale OR u.locale IS NULL')
                ->setParameter('locale', $this->getLocale())
            ;
        }

        return $qb->getQuery();
    }

    /**
     * Returns an empty user instance
     *
     * @return \Platformd\UserBundle\Entity\User
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


    public function setNewPassword(User $user)
    {
        $user->setPlainPassword(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
    }

    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    private function getLocale()
    {
        if (!$this->container) {
            return false;
        }

        return $this->container->get('session')->getLocale();
    }
}

