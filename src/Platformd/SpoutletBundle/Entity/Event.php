<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User,
    Platformd\SpoutletBundle\Entity\MetroArea;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\EventRepository")
 */
class Event
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
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User", mappedBy="events")
     */
    private $users;

    /**
     * @var boolean $ready
     *
     * @ORM\Column(name="ready", type="boolean")
     */
    private $ready;

    /**
     * @var boolean $published
     *
     * @ORM\Column(name="published", type="boolean")
     */
    private $published;

    /**
     * @var datetime $starts_at
     *
     * @ORM\Column(name="starts_at", type="datetime")
     */
    private $starts_at;

    /**
     * @var datetime $ends_at
     *
     * @ORM\Column(name="ends_at", type="datetime")
     */
    private $ends_at;

    /**
     * @var string $city
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string $country
     *
     * @ORM\Column(name="country", type="string", length=255)
     */
    private $country;

    /**
     * @var Platformd\SpoutletBundle\Entity\MetroArea
     *
     * @ORM\ManyToOne(targetEntity="MetroArea", inversedBy="metro_area")
     * @ORM\JoinColumn(name="metro_area_id", referencedColumnName="id")
     */
    private $metro_area;

    /**
     * @var text $content
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;
    
    /**
     * @var string hosted_by
     *
     * @ORM\Column(name="hosted_by", type="string", length=255)
     */
    private $hosted_by;

    /**
     * @var string game
     *
     * @ORM\Column(name="game", type="string", length=255)
     */
    private $game;

    public function __construct()
    {
        $this->users = new ArrayCollection();
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
     * Get users
     *
     * @param Doctrine\Common\Collections\Collection $users
     */
    public function setUsers(Collection $users)
    {
        $this->users = $users;
    }

    /**
     * Get users
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add an user
     *
     * @param Platformd\UserBundle\Entity\User $user
     */
    public function addUser(User $user)
    {
        $this->users->add($user);
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
     * Set user
     *
     * @param Platformd\UserBundle\Entity\User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return Platformd\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set ready
     *
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }

    /**
     * Get ready
     *
     * @return boolean 
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * Set published
     *
     * @param boolean $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * Get published
     *
     * @return boolean 
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Set starts_at
     *
     * @param datetime $startsAt
     */
    public function setStartsAt($startsAt)
    {
        $this->starts_at = $startsAt;
    }

    /**
     * Get starts_at
     *
     * @return datetime 
     */
    public function getStartsAt()
    {
        return $this->starts_at;
    }

    /**
     * Set ends_at
     *
     * @param datetime $endsAt
     */
    public function setEndsAt($endsAt)
    {
        $this->ends_at = $endsAt;
    }

    /**
     * Get ends_at
     *
     * @return datetime 
     */
    public function getEndsAt()
    {
        return $this->ends_at;
    }

    /**
     * Set city
     *
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set metro_area
     *
     * @param Platformd\SpoutletBundle\Entity\MetroArea $metro_area
     */
    public function setMetroArea($metro_area)
    {
        $this->metro_area = $metro_area;
    }

    /**
     * Get metro_area
     *
     * @return Platformd\SpoutletBundle\Entity\MetroArea
     */
    public function getMetroArea()
    {
        return $this->metro_area;
    }

    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get hosted_by
     *
     * @return string $hosted_by
     */
    public function getHostedBy() 
    {
        return $this->hosted_by;
    }

    /**
     * Set hosted_by
     *
     * @param string $hosted_by
     */
    public function setHostedBy($hosted_by)
    {
        $this->hosted_by = $hosted_by;
    }

     /**
     * Get game
     *
     * @return string $game
     */
    public function getGame() 
    {
        return $this->game;
    }

    /**
     * Set game
     *
     * @param string $game
     */
    public function setGame($game)
    {
        $this->game = $game;
    }

}