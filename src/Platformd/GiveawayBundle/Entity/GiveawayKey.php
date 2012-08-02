<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Superclass\Code;

use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\SpoutletBundle\Entity\Superclass\Pool;

/**
 * Platformd\GiveawayBundle\Entity\GiveawayKey
 * 
 * @ORM\Table(
 *      name="giveaway_key",
 *      indexes={
 *          @ORM\index(name="user_pool_idx", columns={"user", "pool"}),
 *          @ORM\index(name="pool_ip_idx", columns={"pool", "ip_address"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository")
 */
class GiveawayKey extends Code
{
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
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     */
    public function setPool(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
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

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }


}