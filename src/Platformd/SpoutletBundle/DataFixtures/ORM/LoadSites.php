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

    private function createSite($id, $name, $locale, $domain, $supportEmailAddress, $emailFromName, $forwardBaseUrl) {

        $site = new Site();

        $forwardedPaths = array(
          '^/arp',
          '^/forums',
          '^/contact',
          '^/about',
        );

        $site->setName($name);
        $site->setDefaultLocale($locale);
        $site->setFullDomain($domain);
        $site->getSiteConfig()
          ->setSupportEmailAddress($supportEmailAddress)
          ->setAutomatedEmailAddress('noreply@alienwarearena.com')
          ->setEmailFromName($emailFromName)
          ->setForwardBaseUrl($forwardBaseUrl)
          ->setForwardedPaths($forwardedPaths)
        ;

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

        $demo  = $this->createSite(1, 'Demo', 'en', 'demo.alienwarearena.local', 'demo@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $japan = $this->createSite(2, 'Japan', 'ja', 'japan.alienwarearena.local', 'japan@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $china = $this->createSite(3, 'China', 'zh', 'china.alienwarearena.local', 'china@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $na    = $this->createSite(4, 'North America', 'en_US', 'na.alienwarearena.local', 'na@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $eu    = $this->createSite(5, 'Europe', 'en_GB', 'eu.alienwarearena.local', 'eu@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $latam = $this->createSite(6, 'Latin America', 'es', 'latam.alienwarearena.local', 'latam@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $in    = $this->createSite(7, 'India', 'en_IN', 'in.alienwarearena.local', 'in@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $mysg  = $this->createSite(8, 'Singapore', 'en_SG', 'mysg.alienwarearena.local', 'mysg@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');
        $anz   = $this->createSite(9, 'Australia / New Zealand', 'en_AU', 'anz.alienwarearena.local', 'anz@alienwarearena.local', 'Alienware Arena', 'http://www.alienwarearena.com');

        $demo->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals()
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown(false)
          ->setHasGroups()
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews()
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient()
          ->setHasProfile()
          ->setHasForwardOn404(false)
          ->setHasIndex()
          ->setHasAbout()
          ->setHasContact()
          ->setHasSearch()
          ->setHasGiveaways()
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
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient(false)
          ->setHasProfile()
          ->setHasForwardOn404(false)
          ->setHasIndex()
          ->setHasAbout()
          ->setHasContact()
          ->setHasSearch()
          ->setHasGiveaways()
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
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient(false)
          ->setHasProfile()
          ->setHasForwardOn404(false)
          ->setHasIndex()
          ->setHasAbout()
          ->setHasContact()
          ->setHasSearch()
          ->setHasGiveaways()
        ;

        $na->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals()
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown(false)
          ->setHasGroups()
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient()
          ->setHasProfile()
          ->setHasForwardOn404()
          ->setHasIndex()
          ->setHasAbout(false)
          ->setHasContact(false)
          ->setHasSearch()
          ->setHasGiveaways()
        ;

        $eu->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals()
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown(false)
          ->setHasGroups()
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient()
          ->setHasProfile()
          ->setHasForwardOn404()
          ->setHasIndex()
          ->setHasAbout(false)
          ->setHasContact(false)
          ->setHasSearch()
          ->setHasGiveaways()
        ;

        $latam->getSiteFeatures()
          ->setHasArp()
          ->setHasComments()
          ->setHasContests()
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown(false)
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos()
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient()
          ->setHasProfile()
          ->setHasForwardOn404()
          ->setHasIndex()
          ->setHasAbout(false)
          ->setHasContact(false)
          ->setHasSearch()
          ->setHasGiveaways()
        ;

        $in->getSiteFeatures()
          ->setHasArp()
          ->setHasComments(false)
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown(false)
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient()
          ->setHasProfile()
          ->setHasForwardOn404()
          ->setHasIndex()
          ->setHasAbout(false)
          ->setHasContact(false)
          ->setHasSearch()
          ->setHasGiveaways()
        ;

        $mysg->getSiteFeatures()
          ->setHasArp()
          ->setHasComments(false)
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown(false)
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient()
          ->setHasProfile()
          ->setHasForwardOn404()
          ->setHasIndex()
          ->setHasAbout(false)
          ->setHasContact(false)
          ->setHasSearch()
          ->setHasGiveaways()
        ;

        $anz->getSiteFeatures()
          ->setHasArp()
          ->setHasComments(false)
          ->setHasContests(false)
          ->setHasDeals(false)
          ->setHasForums()
          ->setHasGames()
          ->setHasGamesNavDropDown(false)
          ->setHasGroups(false)
          ->setHasMessages()
          ->setHasMicrosoft()
          ->setHasNews(false)
          ->setHasPhotos(false)
          ->setHasSteamXfireCommunities()
          ->setHasSweepstakes(false)
          ->setHasVideo()
          ->setHasWallpapers()
          ->setHasEvents()
          ->setHasHtmlWidgets(false)
          ->setHasFacebook()
          ->setHasGoogleAnalytics()
          ->setHasTournaments()
          ->setHasMatchClient()
          ->setHasProfile()
          ->setHasForwardOn404()
          ->setHasIndex()
          ->setHasAbout(false)
          ->setHasContact(false)
          ->setHasSearch()
          ->setHasGiveaways()
        ;

        $this->manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}

?>
