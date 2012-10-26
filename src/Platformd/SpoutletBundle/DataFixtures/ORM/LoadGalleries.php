<?php

namespace Platformd\SpoutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\SpoutletBundle\Entity\Gallery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadGallery extends AbstractFixture implements OrderedFixtureInterface
{
    private $container;

    public function load($manager)
    {
        /*$gallery1 = new Gallery();
        $gallery1->setName('Events');
        $manager->persist($gallery1);
        $manager->flush();*/
    }

    public function getOrder()
    {
        return 2;
    }
}
