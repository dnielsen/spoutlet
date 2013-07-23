<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;

use Gaufrette\Filesystem;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\ORM\AbstractQuery;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;

use Platformd\UserBundle\Exception\ApiRequestException;

class UserManager extends BaseUserManager
{
    const DEFAULT_SORTING_FIELD = 'username';

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

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function updateUser(UserInterface $user, $andFlush = true)
    {
        parent::updateUser($user, $andFlush);
    }

    public function updateUserAndApi(UserInterface $user, $andFlush = true)
    {
        if ($this->container->getParameter('api_authentication')) {
            $apiManager = $this->container->get('platformd.user.api.manager');

            if (!$apiManager->updateRemoteUserData($user)) {
                throw new ApiRequestException();
            }
        }

        parent::updateUser($user, $andFlush);
    }

    public function getFindUserQuery($search = null, $type='text', $locale=null, $sort_by = self::DEFAULT_SORTING_FIELD)
    {
        $qb = $this
            ->repository
            ->createQueryBuilder('u')
            ->orderBy('u.'.$sort_by)
        ;

        if ($search) {

            $search = trim($search);

            if ($type == 'ip') {
                $qb->leftJoin('u.loginRecords', 'r');
            }

            $where = $type == 'ip' ? 'u.ipAddress = :search OR r.ipAddress = :search' : 'u.username like :search OR u.email LIKE :search';
            $search = $type == 'ip' ? $search : '%'.$search.'%';

            $qb
                ->andWhere($where)
                ->setParameter('search', $search)
            ;
        }

        if ($locale) {
            $qb
                ->andWhere('u.locale = :locale OR u.locale IS NULL')
                ->setParameter('locale', $this->getLocale())
            ;
        }

        $qb->distinct('u.id');

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

        $user->setUuid($this->uuidGen());

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

    private function uuidGen()
    {
        return str_replace("\n", '', `uuidgen -r`);
    }

    public function addLoginRecord($user, $request)
    {
        $loginRecordManager = $this->container->get('platformd.model.login_record_manager');
        $loginRecordManager->recordLogin($user, $request);
    }
}

