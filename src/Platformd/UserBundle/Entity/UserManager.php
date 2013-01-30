<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;

use Gaufrette\Filesystem;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\ORM\AbstractQuery;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;

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

        if ($this->isExpired($user)) {
            throw new CredentialsExpiredException(
                sprintf(
                    'This account has been suspended until %s. If you believe this to be an error, please email contact@alienwarearena.com.',
                    $user->getExpiredUntil() ? $user->getExpiredUntil()->format('Y-m-s') : 'infinity'
                )
            );
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

    /**
     * {@inheritDoc}
     */
    public function updateUser(UserInterface $user, $andFlush = true)
    {
        if ($user instanceof User) {
            $this->updateAvatar($user);
        }

        parent::updateUser($user, $andFlush);
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
        $user->setIpAddress($this->getClientIpAddress());

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

    /**
     * Update a user's avatar
     *
     * @param \Platformd\UserBundle\Entity\User $user
     */
    protected function updateAvatar(User $user)
    {
        // User didn't upload a new avatar
        if (is_null($user->file)) {

            return;
        }

        // todo : use config
        $filename = sha1($user->getUsername().'-'.uniqid()).'.'.$user->file->guessExtension();
        $this->filesystem->write('avatar/'.$filename, file_get_contents($user->file->getPathname()));

        $user->setAvatar($filename);
        $user->disapproveAvatar();
    }

    private function getLocale()
    {
        if (!$this->container) {
            return false;
        }

        return $this->container->get('session')->getLocale();
    }

    private function getClientIpAddress()
    {
        if (!$this->container || !$this->container->isScopeActive('request')) {
            return false;
        }

        return $this->container->get('request')->getClientIp(true);
    }

    private function isExpired(User $user)
    {
        if ($user->isExpired()) {
            return true;
        }

        return 0 < $this->countOtherExpiredUsersByIpAddress($this->getClientIpAddress(), $user->getUsername());
    }

    private function countOtherExpiredUsersByIpAddress($ipAddress, $username)
    {
        return $this->repository->countOtherExpiredUsersByIpAddress($ipAddress, $this->canonicalizeUsername($username));
    }
}

