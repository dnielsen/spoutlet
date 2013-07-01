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

    public function __construct($apiManager, EntityManager $em)
    {
        $this->apiManager  = $apiManager;
        $this->em       = $em;
    }

    public function loadUserByUsername($username)
    {
        // Do we have a local record?
        if ($user = $this->findUserBy(array('username' => $username))) {
            return $user;
        }

        // Try manager
        if ($record = $this->apiManager->getUserByUsername($username)) {
            // Set some fields
            $user = new User();
            $user->setUsername($username);
            $user->setEmail($record['user']['email']);
            $user->setUuid($record['user']['uuid']);
            $user->setCreated($record['user']['created']);
            $user->setUpdated($record['user']['lastUpdated']);
            $user->setEnabled(true);
            $user->setPassword('no_longer_used');
            //$this->em->persist($user);

            return $user;
        }

        throw new UsernameNotFoundException(sprintf('No record found for user %s', $username));
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
}
