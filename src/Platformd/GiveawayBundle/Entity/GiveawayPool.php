<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Superclass\Pool;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\GiveawayBundle\Entity\GiveawayPool
 *
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\GiveawayPoolRepository")
 * @ORM\Table(name="giveaway_pool")
 */
class GiveawayPool extends Pool
{
    /**
     * Many to one with Giveaway
     *
     * @var Giveaway
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\Giveaway", inversedBy="giveawayPools")
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
}