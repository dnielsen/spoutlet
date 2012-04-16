<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\AbstractEvent;

/**
 * Platformd\GiveawayBundle\Entity\GiveawayKey
 * 
 * @ORM\Table(name="giveaway_key")
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository")
 */
class GiveawayKey
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
     * @var string $value
     *
     * @ORM\Column(name="value", type="string", length=255)
     */
    protected $value;

    /**
     * @ORM\JoinColumn(name="pool", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayPool", inversedBy="giveawayKeys", cascade={"persist", "remove", "merge"})
     */
    protected $pool;

    /**
     * The user assigned to this key
     *
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="giveawayKeys", cascade={"persist", "remove", "merge"})
     */
    protected $user;

    /**
     * @ORM\Column(name="assigned_at", type="datetime", nullable=true)
     */
    protected $assignedAt;

    /**
     * Holds the site/locale that this key was assigned under
     *
     * @ORM\Column(name="assigned_site", type="string", nullable=true, length="10")
     */
    protected $assignedSite;

    /**
     * @ORM\Column(name="ip_address", type="string", nullable=true, length=100)
     */
    protected $ipAddress;

    public function __construct($value) 
    {
        $this->setValue($value);    
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
     * Set value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     */
    public function setPool(GiveawayPool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\GiveawayPool
     */
    public function getPool()
    {

        return $this->pool;
    }

    public function assign(User $user, $ipAddress, $site)
    {
        $this->user = $user;
        $this->assignedAt = new \DateTime();
        $this->ipAddress = $ipAddress;
        $this->setAssignedSite($site);
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function getAssignedAt()
    {
        return $this->assignedAt;
    }

    public function getAssignedAtTranslationArray()
    {
        return AbstractEvent::convertDateTimeIntoTranslationArray($this->getAssignedAt());
    }

    public function getAssignedSite()
    {
        return $this->assignedSite;
    }

    public function setAssignedSite($assignedSite)
    {
        $this->assignedSite = $assignedSite;
    }
}