<?php

namespace Platformd\EventBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Platformd\EventBundle\Entity\Event;
use Platformd\EventBundle\Entity\GlobalEvent;
use Platformd\EventBundle\Entity\GroupEvent;
use Platformd\GroupBundle\Entity\Group;

class LoadEvents extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;
    private $manager;

    const NUM_EVENTS = 5;

    private function createEvent($type, $name, $user, $site, $group=null, $online=true, $startsAt=null, $endsAt=null, $externalUrl=null, $location=array(), $private=false)
    {
      $event = $type == 'group' ? new GroupEvent($group) : new GlobalEvent();

      $startsAt = $startsAt ?: new \DateTime();
      $endsAt   = $endsAt   ?: new \DateTime('+1 week');

      $event->setName($name);
      $event->setContent('Automatically generated event');
      $event->setUser($user);
      $event->setApproved(true);
      $event->setOnline($online);
      $event->setStartsAt($startsAt);
      $event->setEndsAt($endsAt);
      $event->setExternalUrl($externalUrl);

      if (!$online) {
        $event->setLocation($location['location']);
        $event->setAddress1($location['add1']);
        $event->setAddress2($location['add2']);
      }

      if ($type == 'group') {
        $event->setPrivate($private);
      } else {
        $event->getSites()->add($site);
      }

      $this->manager->persist($event);
      $user->getPdGroups()->add($event);
      $this->manager->persist($user);

      return $event;
    }

    private function resetAutoIncrementId()
    {
        $con = $this->manager->getConnection();

        $con
            ->prepare("ALTER TABLE `global_event` AUTO_INCREMENT = 1")
            ->execute();

        $con
            ->prepare("ALTER TABLE `group_event` AUTO_INCREMENT = 1")
            ->execute();
    }

    public function load($manager)
    {
        $this->manager = $manager;

        $this->resetAutoIncrementId();

        $site  = $this->manager->getRepository('SpoutletBundle:Site')->find(1);
        $user  = $this->container->get('fos_user.user_manager')->findUserByUsername('admin');
        $group = new Group();

        $group->setName('Event Group');
        $group->setCategory('topic');
        $group->setDescription('Auto generated group');
        $group->setIsPublic(true);
        $group->setOwner($user);
        $group->getSites()->add($site);
        $group->setFeatured(true);
        $this->manager->persist($group);

        // Group Events [Online]
        for ($i=0; $i < self::NUM_EVENTS; $i++) {
          $event = $this->createEvent('group', 'Group Online Event '.$i, $user, $site, $group, true, null, null, null, array(), false);
        }

        // Global Events [Online]
        for ($i=0; $i < self::NUM_EVENTS; $i++) {
          $event = $this->createEvent('global', 'Global Online Event '.$i, $user, $site);
        }

        // Expired Global Events [Online]
        for ($i=0; $i < self::NUM_EVENTS; $i++) {
          $event = $this->createEvent('global', 'Global Expired Event '.$i, $user, $site, null, true, null, new \DateTime());
        }

        // Group Events [Location]
        for ($i=0; $i < self::NUM_EVENTS; $i++) {
          $event = $this->createEvent('group', 'Group Location Event '.$i, $user, $site, $group, false, null, null, null, array(
            'location' => 'Staples Center',
            'add1'     => '1111 S Figueroa St',
            'add2'     => 'Los Angeles, CA 90015',
          ), false);
        }

        $this->manager->flush();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getOrder()
    {
        return 4;
    }
}

?>
