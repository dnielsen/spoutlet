<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\SpoutletBundle\Entity\Superclass\Pool;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\GiveawayBundle\Entity\GiveawayPool
 *
 * @ORM\Entity()
 * @ORM\Table(name="giveaway_pool")
 */
class GiveawayPool extends AbstractPool
{
    /**
     * Many to one with Giveaway
     *
     * @var Giveaway
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\Giveaway", inversedBy="pools", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $giveaway;

    /**
     * @return \Platformd\GiveawayBundle\Entity\Giveaway
     */
    public function getGiveaway()
    {
        return $this->giveaway;
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     */
    public function setGiveaway($giveaway)
    {
        $this->giveaway = $giveaway;
    }

    /**
     * Returns whether or not this pool should be treated as active
     *
     * This goes beyond the normal isActive to check anything else.
     * For example, a GiveawayPool is only active if both the pool and
     * the related Giveaway are active
     *
     * @return boolean
     */
    public function isTotallyActive()
    {
        return $this->getIsActive() && $this->getGiveaway() && ($this->getGiveaway()->isActive() || $this->getGiveaway()->getTestOnly());
    }

    public function getParentName()
    {
        return $this->getGiveaway()->getName();
    }
}
