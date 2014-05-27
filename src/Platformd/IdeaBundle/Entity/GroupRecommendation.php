<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Platformd\GroupBundle\Entity\Group;

/**
 * GroupRecommendation
 * @ORM\Table(name="group_recommendation")
 * @ORM\Entity
 */
class GroupRecommendation
{
    const TYPE_JOIN      = 'join';
    const TYPE_SPONSOR   = 'sponsor';
    const TYPE_SPEAK     = 'speak';
    const TYPE_VOLUNTEER = 'volunteer';

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
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group")
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $referredBy;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;


    /**
     * Constructor
     */
    public function __construct($user, $group, $referredBy, $type=self::TYPE_JOIN)
    {
        $this->user = $user;
        $this->referredBy = $referredBy;

        $this->group = $group;

        $this->type = $type;

        $this->createdAt = new \DateTime();
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
     * Set type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     */
    public function getType()
    {
        return $this->type;
    }
}
