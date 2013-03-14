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

    private function createSite($id, $name, $locale, $domain, $supportEmailAddress) {

        $site = new Site();

        $site->setName($name);
        $site->setDefaultLocale($locale);
        $site->setFullDomain($domain);
        $site->setSupportEmailAddress($supportEmailAddress);

        $this->manager->persist($site);

        return $site;
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

        $demo  = $this->createSite(1, 'Demo', 'en', 'demo.alienwarearena.local', 'demo@alienwarearena.local');
        $japan = $this->createSite(2, 'Japan', 'ja', 'japan.alienwarearena.local', 'japan@alienwarearena.local');
        $china = $this->createSite(3, 'China', 'zh', 'china.alienwarearena.local', 'china@alienwarearena.local');
        $na    = $this->createSite(4, 'North America', 'en_US', 'na.alienwarearena.local', 'na@alienwarearena.local');
        $eu    = $this->createSite(5, 'Europe', 'en_GB', 'eu.alienwarearena.local', 'eu@alienwarearena.local');
        $latam = $this->createSite(6, 'Latin America', 'es', 'latam.alienwarearena.local', 'latam@alienwarearena.local');
        $in    = $this->createSite(7, 'India', 'en_IN', 'in.alienwarearena.local', 'in@alienwarearena.local');
        $mysg  = $this->createSite(8, 'Singapore', 'en_SG', 'mysg.alienwarearena.local', 'mysg@alienwarearena.local');
        $anz   = $this->createSite(9, 'Australia / New Zealand', 'en_AU', 'anz.alienwarearena.local', 'anz@alienwarearena.local');

        $demo->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals()
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown()
          ->setHasGroups()
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews()
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $japan->getSiteFeatures()
          ->setHasArp(false)
          ->setHasComments()
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums(false)
          ->setHasGames(false)
          ->setHasGamesNavDropDown(false)
          ->setHasGroups(false)
          ->setHasMessages(false)
          ->setHasMicrosoft(false)
          ->setHasNews()
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities(false)
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers(false)
        ;

        $china->getSiteFeatures()
          ->setHasArp(false)
          ->setHasComments(false)
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums(false)
          ->setHasGames(false)
          ->setHasGamesNavDropDown(false)
          ->setHasGroups(false)
          ->setHasMessages(false)
          ->setHasMicrosoft()
          ->setHasNews()
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities(false)
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $na->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals()
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown()
          ->setHasGroups()
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $eu->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals()
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown()
          ->setHasGroups()
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $latam->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown()
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $in->getSiteFeatures()
          ->setHasArp()
          ->setHasComments(false)
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown()
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $mysg->getSiteFeatures()
          ->setHasArp()
          ->setHasComments(false)
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown()
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $anz->getSiteFeatures()
          ->setHasArp()
          ->setHasComments(false)
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown()
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
        ;

        $this->manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}

?>
