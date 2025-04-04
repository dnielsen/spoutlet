<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\HomepageBanner
 *
 * @ORM\Table(name="sp_homepage_banner")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\HomepageBannerRepository")
 */
class HomepageBanner
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length=5, nullable=true)
     */
    private $locale;

    /**
     * @var string $thumb
     *
     * @ORM\Column(name="thumb", type="string", length=255)
     */
    private $thumb;

    /**
     * @var \Symfony\Component\HttpFoundation\File\File
     */
    public $thumb_file;

    /**
     * @var string $banner
     *
     * @ORM\Column(name="banner", type="string", length=255)
     */
    private $banner;

    /**
     * @var \Symfony\Component\HttpFoundation\File\File
     */
    public $banner_file;

    /**
     * @var integer $position
     *
     * @ORM\Column(name="position", type="integer")
     */
    private $position = 0;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=255)
     * @Assert\NotBlank
     */
    private $url;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="homepage_banner_site")
     */
    private $sites;

    /**
     * @ORM\Column(name="sites_positions", type="array", nullable=true)
     */
    private $sitesPositions;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="new_window", type="boolean")
     */
    private $newWindow = false;

    public function __construct()
    {
        $this->sitesPositions = array();
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

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set thumb
     *
     * @param string $thumb
     */
    public function setThumb($thumb)
    {
        $this->thumb = $thumb;
    }

    /**
     * Get thumb
     *
     * @return string
     */
    public function getThumb()
    {
        return $this->thumb;
    }

    /**
     * Set banner
     *
     * @param string $banner
     */
    public function setBanner($banner)
    {
        $this->banner = $banner;
    }

    /**
     * Get banner
     *
     * @return string
     */
    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * Set position
     *
     * @param integer $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    public function getSitesPositions()
    {
        return $this->sitesPositions;
    }

    public function setSitesPositions($sitesPositions)
    {
        $this->sitesPositions = $sitesPositions;
    }

    public function getNewWindow()
    {
        return $this->newWindow;
    }

    public function setNewWindow($newWindow)
    {
        $this->newWindow = $newWindow;
    }

    public function getSiteList()
    {
        $siteList = '';
        foreach ($this->getSites() as $site) {
            $siteList .=  '['.$site->getName().']';
        }

        return $siteList;
    }
}
