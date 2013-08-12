<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Entity\SiteFeatures;
use Platformd\SpoutletBundle\Entity\SiteConfig;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\SpoutletBundle\Entity\Site
 *
 * @ORM\Table(name="pd_site")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\SiteRepository")
 */
class Site
{
    const DEFAULT_THEME = 'default';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $name;

    /**
     * @var string $defaultLocale
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $defaultLocale;

    /**
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $fullDomain;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\SiteFeatures", mappedBy="site", cascade={"persist"}, fetch="EAGER")
     */
    private $siteFeatures;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\SiteConfig", mappedBy="site", cascade={"persist"}, fetch="EAGER")
     */
    private $siteConfig;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\CountrySpecificItem", mappedBy="site", cascade={"persist"}, fetch="EAGER")
     */
    private $countrySpecificItems;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $theme = self::DEFAULT_THEME;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\Region", mappedBy="site")
     */
    private $region;

    public function __construct() {
        $this->siteFeatures = new SiteFeatures();
        $this->siteFeatures->setSite($this);

        $this->siteConfig = new SiteConfig();
        $this->siteConfig->setSite($this);

        $this->countrySpecificItems = new ArrayCollection();
    }

    public function __toString() {
         return 'Site => { Id = '.$this->getId().', Name = "'.$this->getName().'" }';
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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    public function getFullDomain()
    {
        return $this->fullDomain;
    }

    public function setFullDomain($value)
    {
        $this->fullDomain = $value;
    }

    public function getSubDomain()
    {
        $arr = explode('.', $this->getFullDomain());
        return $arr[0];
    }

    public function getSiteFeatures()
    {
        return $this->siteFeatures;
    }

    public function setSiteFeatures($value)
    {
        $this->siteFeatures = $value;
        return $this;
    }

    public function getSiteConfig()
    {
        return $this->siteConfig;
    }

    public function setSiteConfig($value)
    {
        $this->siteConfig = $value;
        return $this;
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function setCountrySpecificItems($value)
    {
        $this->countrySpecificItems = $value;
    }

    public function getCountrySpecificItems()
    {
        return $this->countrySpecificItems;
    }

    public function setRegion($value)
    {
        $this->region = $value;
    }

    public function getRegion()
    {
        return $this->region;
    }
}
