<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Platformd\UserBundle\Entity\User;

/**
 * Platformd\GiveawayBundle\Entity\GiveawayKey
 *
 * @ORM\Table(
 *      name="giveaway_key",
 *      indexes={
 *          @ORM\Index(name="user_pool_idx", columns={"user", "pool"}),
 *          @ORM\Index(name="pool_ip_idx", columns={"pool", "ip_address"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository")
 */
class GiveawayKey extends AbstractCode
{
    /**
     * @ORM\JoinColumn(name="pool", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayPool", cascade={"persist", "remove", "merge"})
     */
    protected $pool;

    /**
     * The user assigned to this key
     *
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="giveawayKeys", cascade={"persist", "remove", "merge"})
     */
    protected $user;

    public function setPool(AbstractPool $pool)
    {
        $this->pool = $pool;
    }

    public function getPool()
    {
        return $this->pool;
    }

    public function assign(User $user, $ipAddress, $site, $country)
    {
        $this->user = $user;
        $this->assignedAt = new \DateTime();
        $this->ipAddress = $ipAddress;
        $this->setAssignedSite($site);
        $this->setCountry($country);
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
