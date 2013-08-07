<?php

namespace Platformd\UserBundle\Security\Provider;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class ApiUserProvider implements UserProviderInterface
{
    private $apiManager;
    private $em;
    private $userManager;

    public function __construct($apiManager, EntityManager $em, $userManager)
    {
        $this->apiManager  = $apiManager;
        $this->em          = $em;
        $this->userManager = $userManager;
    }

    public function loadUserByUsername($usernameOrEmail)
    {
        // Do we have a local record?
        try {
            $user = $this->userManager->loadUserByUsername($usernameOrEmail);

            if ($user) {
                return $user;
            }

        } catch (UsernameNotFoundException $e) {
            // Look for remote user
            if ($record = $this->apiManager->getUserByUsernameOrEmail($usernameOrEmail)) {
                if ($record['metaData']['status'] == 200) {

                    $birthdate = $record['data']['birth_date'] ? new \DateTime($record['data']['birth_date']) : null;
                    $expiredUntil = $record['data']['suspended_until'] ? new \DateTime($record['data']['suspended_until']) : null;

                    // Set some fields
                    $user = new User();
                    $user->setUsername($record['data']['username']);
                    $user->setUsernameCanonical($this->canonicalize($record['data']['username']));
                    $user->setEmail($record['data']['email']);
                    $user->setEmailCanonical($this->canonicalize($record['data']['email']));
                    $user->setUuid($record['data']['uuid']);
                    $user->setCreated($record['data']['created']);
                    $user->setUpdated($record['data']['last_updated']);
                    $user->setEnabled(true);
                    $user->setPassword('no_longer_used');
                    $user->setFirstname($record['data']['first_name']);
                    $user->setLastname($record['data']['last_name']);
                    $user->setState($record['data']['state']);
                    $user->setCountry($record['data']['country']);
                    $user->setBirthdate($birthdate);
                    $user->setExpired($record['data']['banned']);
                    $user->setExpiredUntil($expiredUntil);
                    $user->setApiSuccessfulLogin(new \DateTime());

                    return $user;
                }
            }
        }

        throw new UsernameNotFoundException(sprintf('No record found for user %s', $usernameOrEmail));
    }

    public function refreshUser(UserInterface $user)
    {
        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Platformd\UserBundle\Entity\User';
    }

    protected function findUserBy(array $criteria)
    {
        $repository = $this->em->getRepository('UserBundle:User');
        return $repository->findOneBy($criteria);
    }

    protected function canonicalize($string)
    {
        return mb_convert_case($string, MB_CASE_LOWER, mb_detect_encoding($string));
    }
}
