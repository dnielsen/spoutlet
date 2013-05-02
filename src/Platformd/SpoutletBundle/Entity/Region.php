<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Platformd\SpoutletBundle\Entity\Region
 *
 * @ORM\Table(name="region")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\RegionRepository")
 */
class Region
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Country")
     * @ORM\JoinTable(name="region_country")
     */
    protected $countries;

    public function __construct()
    {
        $this->countries = new ArrayCollection();
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

    public function getCountries()
    {
        return $this->countries;
    }

    public function setCountries($countries)
    {
        $this->countries = $countries;
    }
}
