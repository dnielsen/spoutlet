<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\Giveaway
 *
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GiveawayRepository")
 */
class Giveaway extends AbstractEvent
{
    /**
     * One to Many with GiveawayPool
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\GiveawayPool", mappedBy="giveaways")
     */
    protected $giveawayPools;

    public function __construct()
    {
        $this->giveawayPools = new ArrayCollection();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGiveawayPools()
    {
        return $this->giveawayPools;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $giveawayPools
     */
    public function setGiveawayPools($giveawayPools)
    {
        $this->giveawayPools = $giveawayPools;
    }

    /**
     * Add an user
     *
     * @param \Platformd\UserBundle\Entity\GiveawayPool $pool
     */
    public function addUser(GiveawayPool $pool)
    {
        $this->giveawayPools->add($pool);
    }
}