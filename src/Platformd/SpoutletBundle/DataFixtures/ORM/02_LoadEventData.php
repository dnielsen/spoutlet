<?php

namespace Platformd\SploutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\SpoutletBundle\Entity\Event;

class LoadEventData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load($manager)
    {
        // Past event
        $event = new Event();
        $event->setName('StarCraft 2 — Blablabla');
        $event->setReady(true);
        $event->setPublished(true);
        $event->setStartsAt(new \DateTime('august 1, 2010'));
        $event->setEndsat(new \DateTime('september 1, 2010'));
        $event->setCity('Lyon');
        $event->setCountry('France');
        $event->setMetroArea($this->getReference('metro-area-one'));
        $event->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam tempor est sem, vitae interdum odio. Praesent varius, tortor eget venenatis molestie, dui urna tristique nisl, a tincidunt quam nulla ac nisl. Mauris nulla lacus, lobortis dictum luctus ut, condimentum a sapien. Morbi dignissim, nulla non hendrerit molestie, dolor nisl ultrices ipsum, sit amet pharetra dolor metus vitae eros. Pellentesque ac turpis sapien, id commodo justo. Quisque commodo cursus tristique. Phasellus sit amet sem felis, et hendrerit mauris.');
        $event->setHostedBy('KnpLabs Lyon');
        $event->setGame('StarCraft 2');

        $manager->persist($event);

        // Current event
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

        // Upcoming Event
        $event = new Event();
        $event->setName('Batman Arkham City — Race to 100%');
        $event->setReady(true);
        $event->setPublished(true);
        $event->setStartsAt(new \DateTime('november 1'));
        $event->setEndsAt(new \DateTime('december 1'));
        $event->setCity('Nashville');
        $event->setCountry('USA');
        $event->setMetroArea($this->getReference('metro-area-two'));
        $event->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam tempor est sem, vitae interdum odio. Praesent varius, tortor eget venenatis molestie, dui urna tristique nisl, a tincidunt quam nulla ac nisl. Mauris nulla lacus, lobortis dictum luctus ut, condimentum a sapien. Morbi dignissim, nulla non hendrerit molestie, dolor nisl ultrices ipsum, sit amet pharetra dolor metus vitae eros. Pellentesque ac turpis sapien, id commodo justo. Quisque commodo cursus tristique. Phasellus sit amet sem felis, et hendrerit mauris.');
        $event->setHostedBy('KnpLabs Nashville');
        $event->setGame('Batman Arkham City');

        $manager->persist($event);

        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}