<?php

namespace Platformd\GroupBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Platformd\GroupBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\Location;

class LoadGroups extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;
    private $manager;

    const NUM_GROUPS = 5;

    private function createGroup($name, $owner, $site, $category='topic', $isPublic=true, $featured=false)
    {

      $group = new Group();

      $group->setName($name);
      $group->setCategory($category);
      $group->setDescription('Auto generated group');
      $group->setIsPublic($isPublic);
      $group->setOwner($owner);
      $group->getSites()->add($site);
      $group->setFeatured($featured);

      if ($category == 'location') {
        $location = new Location();

        $location->setCity('Los Angeles');
        $location->setStateProvince('CA');

        $this->manager->persist($location);

        $group->setLocation($location);
      }

      $this->manager->persist($group);
      $owner->getPdGroups()->add($group);
      $this->manager->persist($owner);

      return $group;
    }

    private function resetAutoIncrementId()
    {
        $con = $this->manager->getConnection();

        $con
            ->prepare("ALTER TABLE `pd_groups` AUTO_INCREMENT = 1")
            ->execute();
    }

    public function load($manager)
    {
        $this->manager = $manager;

        $this->resetAutoIncrementId();

        $site = $this->manager->getRepository('SpoutletBundle:Site')->find(1);
        $user = $this->container->get('fos_user.user_manager')->findUserByUsername('admin');

        // Public topic groups
        for ($i=0; $i < self::NUM_GROUPS; $i++) {
          $group = $this->createGroup('Topic Group '.$i, $user, $site);
        }

        // Public location groups
        for ($i=0; $i < self::NUM_GROUPS; $i++) {
          $group = $this->createGroup('Location Group '.$i, $user, $site, 'location');
        }

        // Private topic groups
        for ($i=0; $i < self::NUM_GROUPS; $i++) {
          $group = $this->createGroup('Private Group '.$i, $user, $site, 'topic', false);
        }

        // Public featured topic groups
        for ($i=0; $i < self::NUM_GROUPS; $i++) {
          $group = $this->createGroup('Featured Group '.$i, $user, $site, 'topic', true, true);
        }

        $this->manager->flush();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getOrder()
    {
        return 3;
    }
}

?>
