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
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site", cascade={"persist"}, inversedBy="region")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id")
     */
    private $site;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Country", inversedBy="regions")
     * @ORM\JoinTable(name="region_country")
     */
    private $countries;

    public function __construct()
    {
        $this->countries = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
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
