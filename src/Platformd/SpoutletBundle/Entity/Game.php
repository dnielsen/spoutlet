<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;

/**
 * Platformd\SpoutletBundle\Entity\Game
 *
 * @ORM\Table(name="pd_game")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GameRepository")
 */
class Game
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
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string $category
     *
     * @ORM\Column(name="category", type="string", length=50)
     */
    private $category;

    /**
     * @var string $facebookFanpageUrl
     *
     * @ORM\Column(name="facebookFanpageUrl", type="string", length=255)
     */
    private $facebookFanpageUrl;

    /**
     * The logo for the game
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $logo;

    /**
     * An image that contains the logos for the individual publisher/developer logos
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $publisherLogos;

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

    /**
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set category
     *
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set facebookFanpageUrl
     *
     * @param string $facebookFanpageUrl
     */
    public function setFacebookFanpageUrl($facebookFanpageUrl)
    {
        $this->facebookFanpageUrl = $facebookFanpageUrl;
    }

    /**
     * Get facebookFanpageUrl
     *
     * @return string 
     */
    public function getFacebookFanpageUrl()
    {
        return $this->facebookFanpageUrl;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $logo
     */
    public function setLogo(Media $logo)
    {
        $this->logo = $logo;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getPublisherLogos()
    {
        return $this->publisherLogos;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $publisherLogos
     */
    public function setPublisherLogos(Media $publisherLogos)
    {
        $this->publisherLogos = $publisherLogos;
    }
}