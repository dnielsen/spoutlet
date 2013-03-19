<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
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
     * @ORM\Column(name="has_giveaways", type="boolean")
     */
    private $hasGiveaways = false;

    /**
     * @ORM\Column(name="has_events", type="boolean")
     */
    private $hasEvents = false;

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

    public function getHasGiveaways()
    {
        return $this->hasGiveaways;
    }

    public function setHasGiveaways($value)
    {
        $this->hasGiveaways = $value;
        return $this;
    }

    public function getHasEvents()
    {
        return $this->hasEvents;
    }

    public function setHasEvents($value)
    {
        $this->hasEvents = $value;
        return $this;
    }

}
