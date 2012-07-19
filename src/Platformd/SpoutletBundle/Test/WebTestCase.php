<?php

namespace Platformd\SpoutletBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Platformd\UserBundle\DataFixtures\ORM\LoadUsers;

class WebTestCase extends BaseWebTestCase
{
    public function loadUsers()
    {
        $em = $this->getEntityManager();

        $em
            ->createQuery('DELETE FROM UserBundle:User')
            ->execute()
        ;

        $purger   = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);

        $loadUsers = new LoadUsers();
        $loadUsers->setContainer($this->getContainer());
        $fixtures = array($loadUsers);

        $executor->execute($fixtures, false);
    }

    /**
     * Deletes all records in the given model
     *
     * @param $alias
     */
    protected function emptyModel($alias)
    {
        $this->getEntityManager()
            ->createQuery(sprintf('DELETE FROM %s', $alias))
            ->execute()
        ;
    }

    /**
     * @param $username
     * @return \Platformd\UserBundle\Entity\User
     */
    protected function findUser($username)
    {
        return $this->getEntityManager()
            ->getRepository('UserBundle:User')
            ->findOneBy(array('username' => $username))
        ;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine')->getEntityManager();
    }

    protected function getContainer()
    {
        return self::$kernel->getContainer();
    }

    protected function getService($service)
    {
        return $this->getContainer()->get($service);
    }
}