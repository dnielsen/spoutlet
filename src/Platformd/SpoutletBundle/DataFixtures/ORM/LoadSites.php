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

    private function createSite($id, $name, $locale, $domain, $supportEmailAddress, $noReplyEmailAddress, $emailFromName, $forwardBaseUrl, $theme = null) {

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

        if($theme != null)
            $site->setTheme($theme);

        $site->getSiteConfig()
          ->setSupportEmailAddress($supportEmailAddress)
          ->setAutomatedEmailAddress($noReplyEmailAddress)
          ->setEmailFromName($emailFromName)
          ->setForwardBaseUrl($forwardBaseUrl)
          ->setForwardedPaths($forwardedPaths)
        ;

        $this->manager->persist($site);
        $this->manager->flush();

        $esReg = $site->createEntrySetRegistration();
        $this->manager->persist($esReg);
        $this->manager->flush();

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

        $demo  = $this->createSite(1, 'Demo',           'en',       'demo.alienwarearena.local',    'demo@alienwarearena.local',    'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $japan = $this->createSite(2, 'Japan',          'ja',       'japan.alienwarearena.local',   'japan@alienwarearena.local',   'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $china = $this->createSite(3, 'China',          'zh',       'china.alienwarearena.local',   'china@alienwarearena.local',   'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $na    = $this->createSite(4, 'North America',  'en_US',    'na.alienwarearena.local',      'na@alienwarearena.local',      'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $eu    = $this->createSite(5, 'Europe',         'en_GB',    'eu.alienwarearena.local',      'eu@alienwarearena.local',      'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $latam = $this->createSite(6, 'Latin America',  'es',       'latam.alienwarearena.local',   'latam@alienwarearena.local',   'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $in    = $this->createSite(7, 'India',          'en_IN',    'in.alienwarearena.local',      'in@alienwarearena.local',      'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $mysg  = $this->createSite(8, 'Singapore',      'en_SG',    'mysg.alienwarearena.local',    'mysg@alienwarearena.local',    'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $anz   = $this->createSite(9, 'Australia / New Zealand','en_AU','anz.alienwarearena.local', 'anz@alienwarearena.local',     'noreply@alienwarearena.com', 'Alienware Arena', 'http://www.alienwarearena.com');
        $campsite  = $this->createSite(10, 'Campsite',  'en_dev',   'campsite.local',               'support@campsite.org',         'noreply@campsite.org',       'Campsite',        'http://www.campsite.local', 'ideacontest');

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

        $campsite->getSiteFeatures()
            ->setHasArp(false)
            ->setHasComments(false)
            ->setHasContests(false)
            ->setHasDeals(false)
            ->setHasForums(false)
            ->setHasGames(false)
            ->setHasGamesNavDropDown(false)
            ->setHasGroups()
            ->setHasMessages(false)
            ->setHasMicrosoft(false)
            ->setHasNews(false)
            ->setHasPhotos(false)
            ->setHasSteamXfireCommunities(false)
            ->setHasSweepstakes(false)
            ->setHasVideo(false)
            ->setHasWallpapers(false)
            ->setHasEvents()
            ->setHasHtmlWidgets()
            ->setHasFacebook(false)
            ->setHasGoogleAnalytics(false)
            ->setHasTournaments(false)
            ->setHasMatchClient(false)
            ->setHasProfile(false)
            ->setHasForwardOn404(false)
            ->setHasIndex()
            ->setHasAbout()
            ->setHasContact()
            ->setHasSearch(false)
            ->setHasGiveaways(false)
        ;

        $this->manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}

?>
