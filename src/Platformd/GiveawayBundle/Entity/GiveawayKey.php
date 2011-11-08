<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Platformd\UserBundle\Entity\User;

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
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="giveawayKeys", cascade={"persist", "remove", "merge"})
     */
    protected $user;

    /**
     * @ORM\Column(name="assigned_at", type="datetime", nullable=true)
     */
    protected $assignedAt;

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
     * @param Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     */
    public function setPool(GiveawayPool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return Platformd\GiveawayBundle\Entity\GiveawayPool
     */
    public function getPool()
    {

        return $this->pool;
    }

    public function assign(User $user)
    {
        $this->user = $user;
        $this->assignedAt = new \DateTime();
    }
}