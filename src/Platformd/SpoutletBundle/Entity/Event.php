<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\Event
 *
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\EventRepository")
 */
class Event extends AbstractEvent
{
    /**
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User", mappedBy="events")
     */
    protected $users;

    /**
     * @var string $city
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    protected $city;

    /**
     * @var string $country
     *
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     */
    protected $country;

    /**
     * @var string $location
     *
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     */
    protected $location;

    /**
     * @var string hosted_by
     *
     * @ORM\Column(name="hosted_by", type="string", length=255, nullable=true)
     */
    protected $hosted_by;

    /**
     * @var string game
     *
     * @ORM\Column(name="game", type="string", length=255, nullable=true)
     */
    protected $game;
    
    /**
    * @var string url_redirect
    *
    * @ORM\Column(name="url_redirect", type="string", length=255, nullable=true)
    */
    protected $url_redirect;

    public function __construct()
    {
        $this->users = new ArrayCollection();
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
    
    /**
    * Get url_redirect
    *
    * @param string $url_redirect
    */
    public function getUrlRedirect()
    {
        return $this->url_redirect;
    }
    
    /**
    * Set url_redirect
    *
    * @param string $url_redirect
    */
    public function setUrlRedirect($url_redirect)
    {
        $this->url_redirect = $url_redirect;
    }    
    
    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getCityCountryLocationString()
    {
        $parts = array();

        if ($this->getCity()) {
            $parts[] = $this->getCity();
        }

        if ($this->getLocation()) {
            $parts[] = $this->getLocation();
        }

        if ($this->getCountry()) {
            $parts[] = $this->getCountry();
        }

        return implode(', ', $parts);
    }

    /**
     * Returns the route name to this item's show page
     *
     * @return string
     */
    public function getShowRouteName()
    {
        return 'events_detail';
    }
}