<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * @ORM\Table(name="pd_site_features")
 * @ORM\Entity()
 */
class SiteFeatures
{

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site", inversedBy="siteFeatures", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $site;

    /**
     * @ORM\Column(name="has_video", type="boolean")
     */
    private $hasVideo = false;

    /**
     * @ORM\Column(name="has_steam_xfire_communities", type="boolean")
     */
    private $hasSteamXfireCommunities = false;

    /**
     * @ORM\Column(name="has_sweepstakes", type="boolean")
     */
    private $hasSweepstakes = false;

    /**
     * @ORM\Column(name="has_forums", type="boolean")
     */
    private $hasForums = false;

    /**
     * @ORM\Column(name="has_arp", type="boolean")
     */
    private $hasArp = false;

    /**
     * @ORM\Column(name="has_news", type="boolean")
     */
    private $hasNews = false;

    /**
     * @ORM\Column(name="has_deals", type="boolean")
     */
    private $hasDeals = false;

    /**
     * @ORM\Column(name="has_games", type="boolean")
     */
    private $hasGames = false;

    /**
     * @ORM\Column(name="has_games_nav_drop_down", type="boolean")
     */
    private $hasGamesNavDropDown = false;

    /**
     * @ORM\Column(name="has_messages", type="boolean")
     */
    private $hasMessages = false;

    /**
     * @ORM\Column(name="has_groups", type="boolean")
     */
    private $hasGroups = false;

    /**
     * @ORM\Column(name="has_wallpapers", type="boolean")
     */
    private $hasWallpapers = false;

    /**
     * @ORM\Column(name="has_microsoft", type="boolean")
     */
    private $hasMicrosoft = false;

    /**
     * @ORM\Column(name="has_photos", type="boolean")
     */
    private $hasPhotos = false;

    /**
     * @ORM\Column(name="has_contests", type="boolean")
     */
    private $hasContests = false;

    /**
     * @ORM\Column(name="has_comments", type="boolean")
     */
    private $hasComments = false;

    /**
     * @ORM\Column(name="has_events", type="boolean")
     */
    private $hasEvents = false;

    /**
     * @ORM\Column(name="has_html_widgets", type="boolean")
     */
    private $hasHtmlWidgets = false;


    /**
     * @ORM\Column(name="has_facebook", type="boolean")
     */
    private $hasFacebook = false;

    /**
     * @ORM\Column(name="has_google_analytics", type="boolean")
     */
    private $hasGoogleAnalytics = false;

    /**
     * @ORM\Column(name="has_tournaments", type="boolean")
     */
    private $hasTournaments = false;

    /**
     * @ORM\Column(name="has_match_client", type="boolean")
     */
    private $hasMatchClient = false;

    /**
     * @ORM\Column(name="has_profile", type="boolean")
     */
    private $hasProfile = false;

    /**
     * @ORM\Column(name="has_forward_on_404", type="boolean")
     */
    private $hasForwardOn404 = false;

    /**
     * @ORM\Column(name="has_index", type="boolean")
     */
    private $hasIndex = false;

    /**
     * @ORM\Column(name="has_about", type="boolean")
     */
    private $hasAbout = false;

    /**
     * @ORM\Column(name="has_contact", type="boolean")
     */
    private $hasContact = false;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setSite($value)
    {
        $this->site = $value;
        return $this;
    }

    public function getHasVideo()
    {
        return $this->hasVideo;
    }

    public function setHasVideo($value = true)
    {
        $this->hasVideo = $value;
        return $this;
    }

    public function getHasSteamXfireCommunities()
    {
        return $this->hasSteamXfireCommunities;
    }

    public function setHasSteamXfireCommunities($value = true)
    {
        $this->hasSteamXfireCommunities = $value;
        return $this;
    }

    public function getHasSweepstakes()
    {
        return $this->hasSweepstakes;
    }

    public function setHasSweepstakes($value = true)
    {
        $this->hasSweepstakes = $value;
        return $this;
    }

    public function getHasForums()
    {
        return $this->hasForums;
    }

    public function setHasForums($value = true)
    {
        $this->hasForums = $value;
        return $this;
    }

    public function getHasArp()
    {
        return $this->hasArp;
    }

    public function setHasArp($value = true)
    {
        $this->hasArp = $value;
        return $this;
    }

    public function getHasNews()
    {
        return $this->hasNews;
    }

    public function setHasNews($value = true)
    {
        $this->hasNews = $value;
        return $this;
    }

    public function getHasDeals()
    {
        return $this->hasDeals;
    }

    public function setHasDeals($value = true)
    {
        $this->hasDeals = $value;
        return $this;
    }

    public function getHasGames()
    {
        return $this->hasGames;
    }

    public function setHasGames($value = true)
    {
        $this->hasGames = $value;
        return $this;
    }

    public function getHasGamesNavDropDown()
    {
        return $this->hasGamesNavDropDown;
    }

    public function setHasGamesNavDropDown($value = true)
    {
        $this->hasGamesNavDropDown = $value;
        return $this;
    }

    public function getHasMessages()
    {
        return $this->hasMessages;
    }

    public function setHasMessages($value = true)
    {
        $this->hasMessages = $value;
        return $this;
    }

    public function getHasGroups()
    {
        return $this->hasGroups;
    }

    public function setHasGroups($value = true)
    {
        $this->hasGroups = $value;
        return $this;
    }

    public function getHasWallpapers()
    {
        return $this->hasWallpapers;
    }

    public function setHasWallpapers($value = true)
    {
        $this->hasWallpapers = $value;
        return $this;
    }

    public function getHasMicrosoft()
    {
        return $this->hasMicrosoft;
    }

    public function setHasMicrosoft($value = true)
    {
        $this->hasMicrosoft = $value;
        return $this;
    }

    public function getHasPhotos()
    {
        return $this->hasPhotos;
    }

    public function setHasPhotos($value = true)
    {
        $this->hasPhotos = $value;
        return $this;
    }

    public function getHasContests()
    {
        return $this->hasContests;
    }

    public function setHasContests($value = true)
    {
        $this->hasContests = $value;
        return $this;
    }

    public function getHasComments()
    {
        return $this->hasComments;
    }

    public function setHasComments($value = true)
    {
        $this->hasComments = $value;
        return $this;
    }

    public function getHasEvents()
    {
        return $this->hasEvents;
    }

    public function setHasEvents($value = true)
    {
        $this->hasEvents = $value;
        return $this;
    }

    public function getHasHtmlWidgets()
    {
        return $this->hasHtmlWidgets;
    }

    public function setHasHtmlWidgets($value = true)
    {
        $this->hasHtmlWidgets = $value;
        return $this;
    }

    public function getHasFacebook()
    {
        return $this->hasFacebook;
    }

    public function setHasFacebook($value = true)
    {
        $this->hasFacebook = $value;
        return $this;
    }

    public function getHasGoogleAnalytics()
    {
        return $this->hasGoogleAnalytics;
    }

    public function setHasGoogleAnalytics($value = true)
    {
        $this->hasGoogleAnalytics = $value;
        return $this;
    }

    public function getHasTournaments()
    {
        return $this->hasTournaments;
    }

    public function setHasTournaments($value = true)
    {
        $this->hasTournaments = $value;
        return $this;
    }

    public function getHasMatchClient()
    {
        return $this->hasMatchClient;
    }

    public function setHasMatchClient($value = true)
    {
        $this->hasMatchClient = $value;
        return $this;
    }

    public function getHasProfile()
    {
        return $this->hasProfile;
    }

    public function setHasProfile($value = true)
    {
        $this->hasProfile = $value;
        return $this;
    }

    public function getHasForwardOn404()
    {
        return $this->hasForwardOn404;
    }

    public function setHasForwardOn404($value = true)
    {
        $this->hasForwardOn404 = $value;
        return $this;
    }

    public function getHasIndex()
    {
        return $this->hasIndex;
    }

    public function setHasIndex($value = true)
    {
        $this->hasIndex = $value;
        return $this;
    }

    public function getHasAbout()
    {
        return $this->hasAbout;
    }

    public function setHasAbout($value = true)
    {
        $this->hasAbout = $value;
        return $this;
    }

    public function getHasContact()
    {
        return $this->hasContact;
    }

    public function setHasContact($value = true)
    {
        $this->hasContact = $value;
        return $this;
    }
}
