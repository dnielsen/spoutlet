<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\CountryState
 *
 * @ORM\Entity()
 * @ORM\Table(name="country_state")
 */
class CountryState
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Country")
     */
    private $country;

    // Workaround for multiple overlapping branches on staging - remove once this and sweeps v2 are on production
    public function __toString() {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCountry($value)
    {
        $this->country = $value;
    }

    public function getCountry()
    {
        return $this->country;
    }
}
