<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\GiveawayBundle\Entity\AbstractCode;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\AbstractEvent;


/**
 * @ORM\Table(name="deal_code")
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\DealCodeRepository")
 */
class DealCode extends AbstractCode
{
    /**
     * @ORM\JoinColumn(name="pool", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\DealPool", inversedBy="dealCodes", cascade={"persist", "remove", "merge"})
     */
    protected $pool;

    /**
     * The user assigned to this key
     *
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="dealCodes", cascade={"persist", "remove", "merge"})
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Country", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    protected $country;

    public function setPool(AbstractPool $pool)
    {
        $this->pool = $pool;
    }

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

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
    }
}
