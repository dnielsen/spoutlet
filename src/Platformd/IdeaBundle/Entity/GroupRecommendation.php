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
     * @ORM\Column(type="boolean")
     */
    protected $dismissed = false;


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
     * Set group
     *
     * @param Platformd\GroupBundle\Entity\Group $group
     */
    public function setGroup(\Platformd\GroupBundle\Entity\Group $group)
    {
        $this->group = $group;
    }

    /**
     * Get group
     *
     * @return Platformd\GroupBundle\Entity\Group 
     */
    public function getGroup()
    {
        return $this->group;
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

    public function getAction()
    {
        switch ($this->type) {
            case self::TYPE_JOIN:
                return 'join';
            case self::TYPE_VOLUNTEER:
                return 'volunteer for';
            case self::TYPE_SPEAK:
                return 'speak at';
            case self::TYPE_SPONSOR:
                return 'sponsor';
        }
    }

    public function setDismissed($value)
    {
        $this->dismissed = $value;
    }
    public function isDismissed()
    {
        return $this->dismissed;
    }
}
