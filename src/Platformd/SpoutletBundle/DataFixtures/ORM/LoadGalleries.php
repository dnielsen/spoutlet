<?php

namespace Platformd\SpoutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\SpoutletBundle\Entity\Gallery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadGalleries extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;
    private $manager;

    public function createGallery($name, $site, $user, $category)
    {
        $gallery = new Gallery();
        $gallery->setName($name);
        $gallery->getSites()->add($site);
        $gallery->getCategories()->add($category);
        $gallery->setOwner($user);
        $this->manager->persist($gallery);

        return $gallery;
    }

    public function load($manager)
    {
        $this->manager = $manager;
        $site          = $this->manager->getRepository('SpoutletBundle:Site')->find(1);
        $videoCategory = $this->manager->getRepository('SpoutletBundle:GalleryCategory')->findOneByName('video');
        $imageCategory = $this->manager->getRepository('SpoutletBundle:GalleryCategory')->findOneByName('image');
        $user          = $this->container->get('fos_user.user_manager')->findUserByUsername('admin');

        $videoGallery = $this->createGallery('Videos', $site, $user, $videoCategory);
        $imageGallery = $this->createGallery('Images', $site, $user, $imageCategory);

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
