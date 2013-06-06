<?php

namespace Platformd\SpoutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\SpoutletBundle\Entity\GalleryCategory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCategories extends AbstractFixture implements OrderedFixtureInterface
{
    private $container;

    public function load($manager)
    {
        $category = new GalleryCategory();
        $category->setName('image');
        $manager->persist($category);

        $category = new GalleryCategory();
        $category->setName('video');
        $manager->persist($category);

        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}
