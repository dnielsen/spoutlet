<?php

namespace Platformd\SpoutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\SpoutletBundle\Entity\Site;

class LoadSites extends AbstractFixture implements OrderedFixtureInterface
{
    private $container;
    private $manager;

    private function createSite($id, $name, $locale, $domain) {

        $siteRepo   = $this->manager->getRepository('SpoutletBundle:Site');
        $site       = $siteRepo->find($id) ?: new Site();

        $site->setName($name);
        $site->setDefaultLocale($locale);
        $site->setSubdomain($domain);

        $this->manager->persist($site);
    }

    private function resetAutoIncrementId() {
        $con = $this->manager->getConnection();

        $con
            ->prepare("ALTER TABLE `pd_site` AUTO_INCREMENT = 1")
            ->execute();
    }

    public function load($manager)
    {
        $this->manager = $manager;

        $this->resetAutoIncrementId();

        $this->createSite(1, 'Demo', 'en', 'demo');
        $this->createSite(2, 'Japan', 'ja', 'japan');
        $this->createSite(3, 'China', 'zh', 'china');
        $this->createSite(4, 'North America', 'en_US', 'na');
        $this->createSite(5, 'Europe', 'en_GB', 'eu');
        $this->createSite(6, 'Latin America', 'es', 'latam');
        $this->createSite(7, 'India', 'en_IN', 'in');
        $this->createSite(8, 'Singapore', 'en_SG', 'mysg');
        $this->createSite(9, 'Australia / New Zealand', 'en_AU', 'anz');

        $this->manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}

?>
