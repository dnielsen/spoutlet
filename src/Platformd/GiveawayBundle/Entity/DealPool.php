<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\GiveawayBundle\Entity\DealPool
 *
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\DealPoolRepository")
 * @ORM\Table(name="deal_pool")
 */
class DealPool extends AbstractPool
{
    /**
     * Many to one with Deal
     *
     * @var Deal
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\Deal", inversedBy="pools")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $deal;

      /**
     * @var boolean $ready
     *
     * @ORM\Column(type="boolean")
     */
    protected $keysAreUrls = false;

    /**
     * @var \Platformd\SpoutletBundle\Entity\Country[]
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Country")
     */
    protected $allowedCountries;

    /**
     * @return \Platformd\GiveawayBundle\Entity\Deal
     */
    public function getDeal()
    {
        return $this->deal;
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\Deal $deal
     */
    public function setDeal($deal)
    {
        $this->deal = $deal;
    }

     /**
     * @return boolean
     */
    public function getKeysAreUrls()
    {
        return $this->keysAreUrls;
    }

    /**
     * @param boolean $value
     */
    public function setKeysAreUrls($value)
    {
        $this->keysAreUrls = $value;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Country[]
     */
    public function getAllowedCountries()
    {
        return $this->allowedCountries;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Country[] $allowedCountries
     */
    public function setAllowedCountries($allowedCountries)
    {
        $this->allowedCountries = $allowedCountries;
    }

    /*

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
        return $this->getIsActive() && $this->getDeal() && ($this->getDeal()->isActive() || $this->getDeal()->getTestOnly());
    }

    public function getParentName()
    {
        return $this->getDeal()->getName();
    }
}
