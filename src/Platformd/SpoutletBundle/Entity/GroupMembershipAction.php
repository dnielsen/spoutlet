<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\GroupMembershipAction
 *
 * @ORM\Table(name="pd_group_membership_actions")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupMembershipActionRepository")
 */
class GroupMembershipAction
{
    const ACTION_JOINED                         = 'JOINED';
    const ACTION_JOINED_APPLICATION_ACCEPTED    = 'JOINED_APPLICATION_ACCEPTED';
    const ACTION_LEFT                           = 'LEFT';

    private static $validActions = array(
        self::ACTION_JOINED,
        self::ACTION_JOINED_APPLICATION_ACCEPTED,
        self::ACTION_LEFT,
    );

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

     /**
     * @Assert\NotNull()
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $action;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Group", inversedBy="membershipActions")
     * @ORM\JoinColumn(onDelete="SET NULL", referencedColumnName="id")
     */
    private $group;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $user;

    public function getId()
    {
        return $this->id;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($value)
    {
        $this->group = $value;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($value)
    {
        $this->user = $value;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($value)
    {
        if (!in_array($value, self::$validActions)) {
            throw new \InvalidArgumentException(sprintf('Invalid membership action "%s" given', $value));
        }

        $this->action = $value;
    }
}
