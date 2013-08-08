<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\CanonicalizerInterface;

use Gaufrette\Filesystem;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\ORM\AbstractQuery;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Platformd\UserBundle\Exception\ApiRequestException;
use Doctrine\ORM\EntityManager;

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

    protected $translator;

    /**
     * Constructor.
     *
     * @param EncoderFactoryInterface $encoderFactory
     * @param CanonicalizerInterface  $usernameCanonicalizer
     * @param CanonicalizerInterface  $emailCanonicalizer
     * @param EntityManager           $em
     * @param string                  $class
     * @param translator              $translator
     */
    public function __construct(EncoderFactoryInterface $encoderFactory, CanonicalizerInterface $usernameCanonicalizer, CanonicalizerInterface $emailCanonicalizer, EntityManager $em, $class, $translator)
    {
        parent::__construct($encoderFactory, $usernameCanonicalizer, $emailCanonicalizer, $em, $class);

        $this->translator = $translator;
    }

    public function getCountryLocaleForUser($user)
    {
        $countryRepo = $this->em->getRepository('SpoutletBundle:Country');
        $country     = $countryRepo->findOneByCode($user->getCountry());

        if (!$country) {
            return 'en';
        }

        $regionRepo = $this->em->getRepository('SpoutletBundle:Region');
        $site       = $regionRepo->findSiteByCountry($country);

        if (!$site) {
            return 'en';
        }

        return $site->getDefaultLocale() ?: 'en';
    }

    public function isUserAccountComplete($user)
    {
        if (!$user instanceof UserInterface) {
            return true;
        }

        $birthDateRequired = $this->container->get('platformd.util.site_util')->getCurrentSite()->getSiteConfig()->getBirthdateRequired();
        $birthDateCheck = $birthDateRequired ? $user->getBirthdate() : true;

        $userAccountComplete = $user->getUsername() && $user->getPassword() && $user->getFirstname() && $user->getLastname()
        && $user->getEmail() && ($user->getSubscribedGamingNews() !== null)
        && ($user->getSubscribedAlienwareEvents() !== null) && $birthDateCheck;

        return $userAccountComplete;
    }

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
                    $this->translator->trans('fos_user.account_banned', array(), 'validators'),
                    $user->getExpiredUntil() ? $user->getExpiredUntil()->format($this->translator->trans('date_format')) : 'infinity'
                )
            );
        }

        return $user;
    }

    public function findByUuid($uuid)
    {
        if (!$uuid) {
            return null;
        }

        return $this->repository->findOneByUuid($uuid);
    }

    public function findByUuidOrCreate($uuid, array $userDetails = array())
    {
        if (!$uuid) {
            return null;
        }

        $user = $this->repository->findOneByUuid($uuid);

        if (!$user) {
            $username            = isset($userDetails['username'])              ? $userDetails['username']                                                          : null;
            $birth_date          = isset($userDetails['birth_date'])            ? \DateTime::createFromFormat(\DateTime::ISO8601, $userDetails['birth_date'])       : null;
            $country             = isset($userDetails['country'])               ? $userDetails['country']                                                           : null;
            $created             = isset($userDetails['created'])               ? \DateTime::createFromFormat(\DateTime::ISO8601, $userDetails['created'])          : null;
            $creation_ip_address = isset($userDetails['creation_ip_address'])   ? $userDetails['creation_ip_address']                                               : null;
            $email               = isset($userDetails['email'])                 ? $userDetails['email']                                                             : null;
            $first_name          = isset($userDetails['first_name'])            ? $userDetails['first_name']                                                        : null;
            $last_name           = isset($userDetails['last_name'])             ? $userDetails['last_name']                                                         : null;
            $last_updated        = isset($userDetails['last_updated'])          ? \DateTime::createFromFormat(\DateTime::ISO8601, $userDetails['last_updated'])     : null;
            $state               = isset($userDetails['state'])                 ? $userDetails['state']                                                             : null;
            $expired             = isset($userDetails['banned'])                ? $userDetails['banned']                                                            : null;
            $suspended_until     = isset($userDetails['suspended_until'])       ? \DateTime::createFromFormat(\DateTime::ISO8601, $userDetails['suspended_until'])  : null;

            $user = parent::createUser();
            $user->setUuid($uuid);
            $user->setUsername($username);
            $user->setBirthdate($birth_date);
            $user->setCountry($country);
            $user->setCreated($created);
            $user->setIpAddress($creation_ip_address);
            $user->setEmail($email);
            $user->setFirstname($first_name);
            $user->setLastname($last_name);
            $user->setUpdated($last_updated);
            $user->setState($state);
            $user->setExpired($expired);
            $user->setExpiredUntil($suspended_until);
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

    public function updateApiPassword(UserInterface $user, $password, $andFlush = true)
    {
        if ($this->container->getParameter('api_authentication')) {
            $apiManager = $this->container->get('platformd.user.api.manager');

            if (!$apiManager->updatePassword($user, $password)) {
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

