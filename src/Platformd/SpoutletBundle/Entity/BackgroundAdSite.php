<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * @ORM\Entity
 **/
class BackgroundAdSite
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", name="site_id")
     */
     private $siteId;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinColumn(name="site_id")
     */
     private $site;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\BackgroundAd")
     */
     private $ad;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
     private $url;

     public function __construct(Site $site = null, $url = null)
     {
         $this->site = $site;
         $this->url = $url;
     }

    /**
     * Get id.
     *
     * @return id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get site.
     *
     * @return site.
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set site.
     *
     * @param site the value to set.
     */
    public function setSite($site)
    {
        $this->site = $site;
    }

    /**
     * Get ad.
     *
     * @return ad.
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set ad.
     *
     * @param ad the value to set.
     */
    public function setAd($ad)
    {
        $this->ad = $ad;
    }

    /**
     * Get url.
     *
     * @return url.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url.
     *
     * @param url the value to set.
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get siteId.
     *
     * @return siteId.
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * Set siteId.
     *
     * @param siteId the value to set.
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;
    }
}

