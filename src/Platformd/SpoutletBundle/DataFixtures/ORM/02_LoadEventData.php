<?php

namespace Platformd\SploutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Platformd\SpoutletBundle\Entity\Event;

class LoadEventData extends AbstractFixture
{
    public function load($manager)
    {
        $event = new Event();
        $event->setName('Portal 2 — Challenge Co-op');
        $event->setReady(true);
        $event->setPublished(true);
        $event->setStartsAt(new \DateTime('october 1'));
        $event->setEndsat(new \DateTime('november 1'));
        $event->setCity('Lyon');
        $event->setCountry('France');
        $event->setMetroArea($this->getReference('metro-area-one'));
        $event->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam tempor est sem, vitae interdum odio. Praesent varius, tortor eget venenatis molestie, dui urna tristique nisl, a tincidunt quam nulla ac nisl. Mauris nulla lacus, lobortis dictum luctus ut, condimentum a sapien. Morbi dignissim, nulla non hendrerit molestie, dolor nisl ultrices ipsum, sit amet pharetra dolor metus vitae eros. Pellentesque ac turpis sapien, id commodo justo. Quisque commodo cursus tristique. Phasellus sit amet sem felis, et hendrerit mauris.');
        $event->setHostedBy('KnpLabs Lyon');
        $event->setGame('Portal 2');

        $manager->persist($event);
        $manager->flush();
    }
}