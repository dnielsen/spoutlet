<?php

namespace Platformd\GiveawayBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\GiveawayBundle\Entity\Giveaway;

class LoadGiveawayData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load($manager)
    {
        $machineGiveaway = new Giveaway();
        $machineGiveaway->setLocale('en');
        $machineGiveaway->setName('My machine giveaway');

        $manager->persist($machineGiveaway);
        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}