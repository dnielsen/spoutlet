<?php

namespace Platformd\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Platformd\UserBundle\Entity\User;
use DateTime;

class LoadUsers implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    protected $container;
    protected $manager;

    public function load($manager)
    {
        $this->manager = $manager;

        $this->resetAutoIncrementId();

        // a normal user
        $user = $this->getUserManipulator()->create(
            'user',
            'user',
            'user@user.com',
            true,
            false
        );
        $user->setFirstName('User');
        $user->setLastName('User');
        $user->setBirthdate(new DateTime('1980-01-01'));
        $this->manager->persist($user);

        // an "organizer" - can create events
        $organizer = $this->getUserManipulator()->create(
            'organizer',
            'organizer',
            'organizer@organizer.com',
            true,
            false
        );
        $this->getUserManipulator()->addRole('organizer', 'ROLE_ORGANIZER');
        $organizer->setFirstName('Organizer');
        $organizer->setLastName('Organizer');
        $organizer->setBirthdate(new DateTime('1980-01-01'));
        $this->manager->persist($organizer);

        // a super admin user (ROLE_SUPER_ADMIN)
        $admin = $this->getUserManipulator()->create(
            'admin',
            'admin',
            'admin@admin.com',
            true,
            true
        );
        $this->getUserManipulator()->addRole('admin', 'ROLE_SUPER_ADMIN');
        $admin->setFirstName('Admin');
        $admin->setLastName('Admin');
        $admin->setBirthdate(new DateTime('1980-01-01'));
        $this->manager->persist($admin);

        $this->manager->flush();
    }

    private function resetAutoIncrementId() {
        $con = $this->manager->getConnection();

        $con
            ->prepare("ALTER TABLE `fos_user` AUTO_INCREMENT = 1")
            ->execute();
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

    private function getUserManager()
    {
        return $this->container->get('fos_user.user_manager');
    }

    public function getOrder()
    {
        return 1;
    }
}

?>
