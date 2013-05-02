<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Entity\SiteFeatures;
use Platformd\SpoutletBundle\Entity\SiteConfig;

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
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $theme = self::DEFAULT_THEME;

    public function __construct() {
        $this->siteFeatures = new SiteFeatures();
        $this->siteFeatures->setSite($this);

        $this->siteConfig = new SiteConfig();
        $this->siteConfig->setSite($this);
    }

    public function __toString()
    {
        return $this->name;
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
}
