<?php

namespace Platformd\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Platformd\UserBundle\Entity\User;

class LoadUsers implements FixtureInterface, ContainerAwareInterface
{
    protected $container;

    public function load($manager)
    {
        // a normal user
        $this->getUserManipulator()->create(
            'user',
            'user',
            'user@user.com',
            true,
            false
        );

        // an "organizer" - can create events
        $this->getUserManipulator()->create(
            'organizer',
            'organizer',
            'organizer@organizer.com',
            true,
            false
        );
        $this->getUserManipulator()->addRole('organizer', 'ROLE_ORGANIZER');

        // a super admin user (ROLE_SUPER_ADMIN)
        $this->getUserManipulator()->create(
            'admin',
            'admin',
            'admin@admin.com',
            true,
            true
        );
        $this->getUserManipulator()->addRole('admin', 'ROLE_SUPER_ADMIN');
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return \FOS\UserBundle\Util\UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->container->get('fos_user.util.user_manipulator');
    }
}