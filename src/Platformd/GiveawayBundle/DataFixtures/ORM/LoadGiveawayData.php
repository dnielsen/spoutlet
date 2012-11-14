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
        $siteRepo   = $manager->getRepository('SpoutletBundle:Site');

        $machineGiveaway = new Giveaway();
        $machineGiveaway->setSites($siteRepo->find(1));
        $machineGiveaway->setName('My machine giveaway');
        $machineGiveaway->setStatus('active');

        $manager->persist($machineGiveaway);
        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}

?>
