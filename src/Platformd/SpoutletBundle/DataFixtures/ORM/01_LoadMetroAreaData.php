<?php

namespace Platformd\SploutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\SpoutletBundle\Entity\MetroArea;

class LoadMetroAreaData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load($manager)
    {
        $metroAreaOne = new MetroArea();
        $metroAreaOne->setLabel('Metro Area One');
        $metroAreaOne->setSlug('metro-area-one');

        $manager->persist($metroAreaOne);

        $metroAreaTwo = new MetroArea();
        $metroAreaTwo->setLabel('Metro Area Two');
        $metroAreaTwo->setSlug('metro-area-two');

        $manager->persist($metroAreaTwo);

        $manager->flush();

        $this->addReference('metro-area-one', $metroAreaOne);
        $this->addReference('metro-area-two', $metroAreaTwo);
    }

    public function getOrder()
    {
        return 1;
    }
}