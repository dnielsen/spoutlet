<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Superclass\Code;

use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\SpoutletBundle\Entity\Superclass\Pool;

/**
 * Platformd\SpoutletBundle\Entity\DealCode
 *
 * @ORM\Table(name="deal_code")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\DealCodeRepository")
 */
class DealCode extends Code
{
    /**
     * @ORM\JoinColumn(name="pool", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\DealPool", inversedBy="dealCodes", cascade={"persist", "remove", "merge"})
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
