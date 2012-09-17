<?php

namespace Platformd\SpoutletBundle\Entity;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\Mapping as ORM;

/**
 * Platformd\SpoutletBundle\Entity\Location
 *
 * @ORM\Table(name="pd_locations")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\LocationRepository")
 */
class Location
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $address1
     * @ORM\Column(name="address1", type="string", length=255, nullable=true)
     */
    private $address1;

    /**
     * @var string $address2
     * @ORM\Column(name="address2", type="string", length=255, nullable=true)
     */
    private $address2;

    /**
     * @var string $city
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string $state_province
     * @ORM\Column(name="state_province", type="string", length=255, nullable=true)
     */
    private $state_province;

    /**
     * @var string $metro_area
     * @ORM\Column(name="metro_area", type="string", length=255, nullable=true)
     */
    private $metro_area;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set address1
     *
     * @param string $address1
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
    }

    /**
     * Get address1
     *
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * Set address2
     *
     * @param string $address2
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
    }

    /**
     * Get address2
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set city
     *
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state_province
     *
     * @param string $state_province
     */
    public function setStateProvince($state_province)
    {
        $this->state_province = $state_province;
    }

    /**
     * Get state_province
     *
     * @return string
     */
    public function getStateProvince()
    {
        return $this->state_province;
    }

    /**
     * Set metro_area
     *
     * @param string $metro_area
     */
    public function setMetroArea($metro_area)
    {
        $this->metro_area = $metro_area;
    }

    /**
     * Get metro_area
     *
     * @return string
     */
    public function getMetroArea()
    {
        return $this->metro_area;
    }

    /**
     * Gets a formatted address string for the location eg 201 San Antonio Circle, Mountain View, CA
     *
     */
    public function getFormattedLocation()
    {
        $location = '';
        if ($this->address1)
        {
            $location .= $this->address1;
        }

        if ($this->address2)
        {
            $location .= ' ' . $this->address2;
        }

        if ($this->city) {
            $location .= ', ' . $this->city;
        }

        if ($this->state_province)
        {
            $location .= ', ' . $this->state_province;
        }

        return $location;
    }
}
