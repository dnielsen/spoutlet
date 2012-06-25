<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\Superclass\Pool;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\DealPool
 *
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\DealPoolRepository")
 * @ORM\Table(name="deal_pool")
 */
class DealPool extends Pool
{
    /**
     * Many to one with Deal
     *
     * @var Deal
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Deal", inversedBy="dealPools")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $deal;

    /**
     * @var \Platformd\SpoutletBundle\Entity\Country[]
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Country")
     */
    protected $allowedCountries;

    /**
     * @return \Platformd\SpoutletBundle\Entity\Deal
     */
    public function getDeal()
    {
        return $this->deal;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     */
    public function setDeal($deal)
    {
        $this->deal = $deal;
    }

    /**
     * Returns whether or not this pool should be treated as active
     *
     * This goes beyond the normal isActive to check anything else.
     * For example, a DealPool is only active if both the pool and
     * the related Deal are active
     *
     * @return boolean
     */
    public function isTotallyActive()
    {
        return $this->getIsActive() && $this->getDeal() && $this->getDeal()->isActive();
    }
}
