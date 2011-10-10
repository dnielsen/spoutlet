<?php

namespace Platformd\UserBundle\Entity;

use Platformd\SpoutletBundle\Entity\Event;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\UserBundle\Entity\User
 *
 * @ORM\Table(name="FosUser")
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
}