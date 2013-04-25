<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Validator\Constraints\UniqueBackgroundAdPerTime;

/**
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\BackgroundAdRepository")
 * @UniqueBackgroundAdPerTime(message="Error! This schedule conflicts with another banner that is scheduled at the same time. Please uncheck the conflicting site.")
 **/

class BackgroundAd
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    protected $title;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $file;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotNull
     */
    protected $dateStart;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotNull
     */
    protected $dateEnd;

    /**
     * @ORM\Column(type="string")
     */
    protected $timezone = "UTC";

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\BackgroundAdSite", mappedBy="ad", cascade={"persist", "delete"}, orphanRemoval=true)
     */
     private $adSites;

    /**
     * @ORM\Column(type="boolean")
     **/
    private $isPublished = false;

    public function __construct($title = null)
    {
        $this->title = $title;
        $this->adSites = new ArrayCollection;
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
     * Get title.
     *
     * @return title.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param title the value to set.
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get path.
     *
     * @return path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path.
     *
     * @param path the value to set.
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get date.
     *
     * @return date.
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    public function getDateStartTimezoned()
    {
        $dt = clone $this->dateStart;
        $dt->setTimezone(new \DateTimeZone($this->timezone));

        return $dt;
    }

    /**
     * Set date.
     *
     * @param date the value to set.
     */
    public function setDateStart($date)
    {
        $this->dateStart = $date;
    }

    /**
     * Get date.
     *
     * @return date.
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    public function getDateEndTimezoned()
    {
        $dt = clone $this->dateEnd;
        $dt->setTimezone(new \DateTimeZone($this->timezone));

        return $dt;
    }

    /**
     * Set date.
     *
     * @param date the value to set.
     */
    public function setDateEnd($date)
    {
        $this->dateEnd = $date;
    }

    /**
     * Get timezone.
     *
     * @return timezone.
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set timezone.
     *
     * @param timezone the value to set.
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Get file.
     *
     * @return file.
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file.
     *
     * @param file the value to set.
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Get adSites.
     *
     * @return adSites.
     */
    public function getAdSites()
    {
        return $this->adSites;
    }

    /**
     * Set adSites.
     *
     * @param adSites the value to set.
     */
    public function addAdSite(BackgroundAdSite $adSite)
    {
        $adSite->setAd($this);
        $this->adSites->add($adSite);
    }

    public function removeAdSite(BackgroundAdSite $adSite)
    {
        $this->adSites->removeElement($adSite);
    }

    public function setAdSites($adSites)
    {
        foreach ($adSites as $adSite) {
            $this->addAdSite($adSite);
        }
    }

    public function getSites()
    {
        return new ArrayCollection(array_map(function($adSite) {
            return $adSite->getSite();
        }, $this->getAdSites()->toArray()));
    }

    public function getSiteIds()
    {
        return array_map(function($adSite) {
            return $adSite->getSiteId();
        }, $this->getAdSites()->toArray());
    }

    public function setSites()
    {
    }

    /**
     * Get isPublished.
     *
     * @return isPublished.
     */
    public function isPublished()
    {
        return $this->isPublished;
    }

    /**
     * Set isPublished.
     *
     * @param isPublished the value to set.
     */
    public function setPublished($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    public function isActive()
    {
        $now = new \DateTime();

        return ($this->getDateStart() <= $now && $this->getDateEnd() >= $now);
    }
}

