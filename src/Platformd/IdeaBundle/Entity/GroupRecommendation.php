<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\GroupBundle\Entity\Group;
use Platformd\UserBundle\Entity\User;

/**
 * @ORM\Table(name="group_recommendation")
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Repository\GroupRecommendationRepository")
 */
class GroupRecommendation
{
    const TYPE_JOIN = 'join';
    const TYPE_SPONSOR = 'sponsor';
    const TYPE_SPEAK = 'speak';
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
     * @param User   $user
     * @param Group  $group
     * @param User   $referredBy
     * @param string $type
     */
    public function __construct(User $user, Group $group, User $referredBy, $type = self::TYPE_JOIN)
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
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set owner
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set group
     *
     * @param Group $group
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;
    }

    /**
     * Get group
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set referredBy
     *
     * @param User $referredBy
     */
    public function setReferredBy(User $referredBy)
    {
        $this->referredBy = $referredBy;
    }

    /**
     * Get referredBy
     *
     * @return User
     */
    public function getReferredBy()
    {
        return $this->referredBy;
    }

    /**
     * Set type
     *
     * @param string $type
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

    /**
     * @param bool $value
     */
    public function setDismissed($value)
    {
        $this->dismissed = $value;
    }

    /**
     * @return bool
     */
    public function isDismissed()
    {
        return $this->dismissed;
    }
}
