<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Site;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="pd_site_config")
 * @ORM\Entity()
 */
class SiteConfig
{

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site", inversedBy="siteConfig", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $site;

    /**
     * @ORM\Column(name="facebook_app_id", type="string", length=50, nullable=true)
     */
    private $facebookAppId;

    /**
     * @ORM\Column(name="google_analytics_account", type="string", length=50, nullable=true)
     */
    private $googleAnalyticsAccount;

    /**
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $supportEmailAddress;

    /**
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $automatedEmailAddress;

    /**
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $emailFromName;

    /**
     *
     * @ORM\Column(type="boolean")
     */
    private $birthdateRequired = true;

    /**
     *
     * @ORM\Column(name="forward_base_url", type="string", nullable=true)
     */
    private $forwardBaseUrl;

    /**
     * @ORM\Column(name="forwarded_paths", type="array", nullable=true)
     */
    private $forwardedPaths;

    public function __construct()
    {
        $this->forwardedPaths = array();
    }

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

    public function getFacebookAppId()
    {
        return $this->facebookAppId;
    }

    public function setFacebookAppId($value)
    {
        $this->facebookAppId = $value;
        return $this;
    }

    public function getGoogleAnalyticsAccount()
    {
        return $this->googleAnalyticsAccount;
    }

    public function setGoogleAnalyticsAccount($value)
    {
        $this->googleAnalyticsAccount = $value;
        return $this;
    }

    public function setAutomatedEmailAddress($value)
    {
        $this->automatedEmailAddress = $value;
        return $this;
    }

    public function getAutomatedEmailAddress()
    {
        return $this->automatedEmailAddress;
    }

    public function setEmailFromName($value)
    {
        $this->emailFromName = $value;
        return $this;
    }

    public function getEmailFromName()
    {
        return $this->emailFromName;
    }

    public function setSupportEmailAddress($supportEmailAddress)
    {
        $this->supportEmailAddress = $supportEmailAddress;
        return $this;
    }

    public function getSupportEmailAddress()
    {
        return $this->supportEmailAddress;
    }

    public function setBirthdateRequired($value)
    {
        $this->birthdateRequired = $value;
        return $this;
    }

    public function getBirthdateRequired()
    {
        return $this->birthdateRequired;
    }

    public function setForwardBaseUrl($value)
    {
        $this->forwardBaseUrl = $value;
        return $this;
    }

    public function getForwardBaseUrl()
    {
        return $this->forwardBaseUrl;
    }

    public function setForwardedPaths($value)
    {
        $this->forwardedPaths = $value;
        return $this;
    }

    public function getForwardedPaths()
    {
        return $this->forwardedPaths;
    }
}
