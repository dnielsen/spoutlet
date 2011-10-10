<?php

namespace Platformd\UserBundle\Entity;

use Platformd\SpoutletBundle\Entity\Event;

use FOS\UserBundle\Entity\User as BaseUser;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;


/**
 * Platformd\UserBundle\Entity\User
 *
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass="Platformd\UserBundle\Entity\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    

    /**
     * @var String $firstname
     *
     * @ORM\Column(type="string", length="255")
     *
     * @Assert\NotBlank(message="Please enter your firstname", groups={"Registration"})
     * @Assert\MinLength(limit="3", message="The firstname is too short", groups={"Registration"})
     * @Assert\MaxLength(limit="255", message="The firstname is too long", groups={"Registration"})
     */
    protected $firstname;

    /**
     * @var String $lastname
     *
     * @ORM\Column(type="string", length="255")
     *
     * @Assert\NotBlank(message="Please enter your lastname", groups={"Registration"})
     * @Assert\MinLength(limit="3", message="The lastname is too short", groups={"Registration"})
     * @Assert\MaxLength(limit="255", message="The lastname is too long", groups={"Registration"})
     */
    protected $lastname;

    /**
     * @var Datetime $birthdate
     *
     * @ORM\Column(type="date")
     * 
     * @Assert\NotBlank(message="Please enter your birthdate", groups={"Registration"})
     * @Assert\Date(groups={"Registration"})
     */
    protected $birthdate;
    
    /**
     * @var String $phone_number
     *
     * @ORM\Column(name="phone_number", type="string", length="255", nullable="true")
     */
    protected $phoneNumber;

    /**
     * @var String $country
     *
     * @ORM\Column(type="string", length="255")
     *
     * @Assert\NotBlank(message="Please enter the country you're currently living in", groups={"Registration"})
     */
    protected $country;

    /**
     * @var String $state
     * 
     * @ORM\Column(type="string", length="255")
     *
     * @Assert\NotBlank(message="Please enter the state you're currently living in", groups={"Registration"})
     */
    protected $state;

    /**
     * @var boolean $has_alienware_system
     *
     * @ORM\Column(name="has_alienware_system", type="boolean", nullable="true")
     */
    protected $hasAlienwareSystem;
    
    /**
     * @var String $latest_news_source
     *
     * @ORM\Column(name="latest_news_source", type="string", length="255")
     */
    protected $latestNewsSource;
    
    /**
     * @var String $subscribe_arena_news
     *
     * @ORM\Column(name="subscribed_arena_news", type="boolean", nullable="true")
     */
    protected $subscribedArenaNews;

    /**
     * @var String $subscribe_gaming_news
     *
     * @ORM\Column(name="subscribed_gaming_news", type="boolean", nullable="true")
     */
    protected $subscribedGamingNews;

    /**
     * @var Boolean $terms_accepted
     *
     * @Assert\True(message="You must accept the terms and conditions and the privacy policies", groups={"Registration"})
     */
    protected $termsAccepted;

    /**
     * @var Platformd\SpoutletBundle\Entity\Event $events
     *
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\Event", mappedBy="user")
     */
    private $events;

    public function __construct() 
    {
        parent::__construct();
        $this->events = new ArrayCollection();   
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
     * Set events
     *
     * @param Platformd\SpoutletBundle\Entity\Event $events
     */
    public function setEvents(Event $events)
    {
        $this->events = $events;
    }

    /**
     * Get events
     *
     * @return Platformd\SpoutletBundle\Entity\Event 
     */
    public function getEvents()
    {
        return $this->events;
    }
    
    /**
     * Get user's lastname
     *
     * @return String
     */
    public function getLastname() 
    {
        
        return $this->lastname;
    }
    
    /**
     * Set user's firstname
     *
     * @param String $lastname 
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * Get user's firstname
     *
     * @return String
     */
    public function getFirstname() 
    {
        
        return $this->firstname;
    }

    /**
     * Set user's firstname
     *
     * @param String $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }
    
    /**
     * Get user's birthdate
     *
     * @return \Datetime
     */
    public function getBirthdate()
    {
        
        return $this->birthdate;
    }
    
    /**
     * Set user's birthdate
     *
     * @param \Datetime $date
     */
    public function setBirthdate(\Datetime $date)
    {
        $this->birthdate = $date;
    }

    /**
     * Get user's phone number
     *
     * @return String
     */
    public function getPhoneNumber() 
    {
        
        return $this->phoneNumber;
    }

    /**
     * Set user's phone number
     *
     * @param String $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }
    
    /**
     * Get user's country 
     *
     * @return String
     */
    public function getCountry() 
    {
        
        return $this->country;
    }

    /**
     * Set user's country
     *
     * @param String $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get user's state 
     *
     * @return String
     */
    public function getState() 
    {
        
        return $this->state;
    }

    /**
     * Set user's state
     *
     * @param String $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }
    
    /** 
     * Do the user have an Alienware system ?
     *
     * @return boolean
     */
    public function getHasAlienwareSystem()
    {
        
        return $this->hasAlienwareSystem;
    }

    /**
     * Set if the user has an alienware system
     *
     * @param boolean $flag
     */
    public function setHasAlienwareSystem($flag)
    {
        $this->hasAlienwareSystem = $flag;
    }

    /**
     * Get latest news' source (where has the user heard about alienware last)
     * 
     * @return String
     */
    public function getLatestNewsSource()
    {
        
        return $this->latestNewsSource;
    }

    /**
     * Set latest news' source
     * 
     * @param String $source
     */
    public function setLatestNewsSource($source)
    {
        $this->latestNewsSource = $source;
    }

    /**
     * Get if the user has subscribed to alienware arena news
     * 
     * @return boolean
     */
    public function getSubscribedArenaNews()
    {
          
        return $this->subscribedArenaNews;
    }

    /**
     * Set if the user has subscribed to alienware arena news
     * 
     * @param boolean $subscribed
     */
    public function setSubscribedArenaNews($subscribed)
    {
        $this->subscribedArenaNews = $subscribed;
    }

    /**
     * Get if the user has subscribed to alienware/dell gaming news
     * 
     * @return boolean
     */
    public function getSubscribedGamingNews()
    {
          
        return $this->subscribedGamingNews;
    }

    /**
     * Set if the user has subscribed to alienware/dell gaming news
     * 
     * @param boolean $subscribed
     */
    public function setSubscribedGamingNews($subscribed)
    {
        $this->subscribedGamingNews;
    }

    /**
     * Get if the user has accepted terms
     * 
     * @return boolean
     */
    public function getTermsAccepted()
    {
        
        return $this->termsAccepted;
    }

    /** 
     * Set if the user has accepted terms
     * 
     * @param boolean $accepted
     */
    public function setTermsAccepted($accepted)
    {
        $this->termsAccepted = $accepted;
    }
}
