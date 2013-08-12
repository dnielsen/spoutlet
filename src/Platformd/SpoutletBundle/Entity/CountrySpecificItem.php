<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\CountrySpecificItem
 *
 * @ORM\Table(name="pd_country_specific_item", indexes={@ORM\index(name="name_site_country_idx", columns={"name", "site_id", "country_code"})})
 * @ORM\Entity()
 */
class CountrySpecificItem
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site", inversedBy="countrySpecificItems", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $site;

    /**
     * @ORM\Column(name="country_code", type="string", length=7)
     * @Assert\NotNull
     */
    private $countryCode;

    public function getId()
    {
        return $this->id;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setSite($value)
    {
        $this->site = $value;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setCountryCode($value)
    {
        $this->countryCode = $value;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }
}
