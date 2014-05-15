<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Platformd\EventBundle\Entity\GlobalEvent;
use Platformd\EventBundle\Entity\GroupEvent;

/**
 * WatchedEventMapping
 * @ORM\Table(name="watched_events")
 * @ORM\Entity
 */
class WatchedEventMapping
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $referredBy;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GlobalEvent")
     * @ORM\JoinColumn(name="global_event", referencedColumnName="id")
     */
    protected $global_event = null;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent")
     * @ORM\JoinColumn(name="group_event", referencedColumnName="id")
     */
    protected $group_event = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * Constructor
     */
    public function __construct($user, $event)
    {
        $this->$user = $user;

        if($event instanceof GlobalEvent)
            $this->$global_event = $event;
        else if($event instanceof GroupEvent)
            $this->$group_event = $event;

        $this->createdAt = new \DateTime();
    }

    /**
     * Set createdAt
     *
     * @param datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return datetime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set owner
     *
     * @param Platformd\UserBundle\Entity\User $owner
     */
    public function setUser(\Platformd\UserBundle\Entity\User $user)
    {
        $this->user = $user;
    }

    /**
     * Get owner
     *
     * @return Platformd\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set global_event
     *
     * @param Platformd\EventBundle\Entity\GlobalEvent $globalEvent
     */
    public function setGlobalEvent(\Platformd\EventBundle\Entity\GlobalEvent $globalEvent)
    {
        $this->global_event = $globalEvent;
    }

    /**
     * Get global_event
     *
     * @return Platformd\EventBundle\Entity\GlobalEvent 
     */
    public function getGlobalEvent()
    {
        return $this->global_event;
    }

    /**
     * Set group_event
     *
     * @param Platformd\EventBundle\Entity\GroupEvent $groupEvent
     */
    public function setGroupEvent(\Platformd\EventBundle\Entity\GroupEvent $groupEvent)
    {
        $this->group_event = $groupEvent;
    }

    /**
     * Get group_event
     *
     * @return Platformd\EventBundle\Entity\GroupEvent 
     */
    public function getGroupEvent()
    {
        return $this->group_event;
    }

    /**
     * Set referredBy
     *
     * @param Platformd\UserBundle\Entity\User $referredBy
     */
    public function setReferredBy(\Platformd\UserBundle\Entity\User $referredBy)
    {
        $this->referredBy = $referredBy;
    }

    /**
     * Get referredBy
     *
     * @return Platformd\UserBundle\Entity\User 
     */
    public function getReferredBy()
    {
        return $this->referredBy;
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
}