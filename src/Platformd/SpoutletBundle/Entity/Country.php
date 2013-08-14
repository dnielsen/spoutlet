<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\Country
 *
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\CountryRepository")
 * @ORM\Table(name="country")
 */
class Country
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
     * @var string $code
     *
     * @ORM\Column(name="code", type="string", length=10)
     * @Assert\NotNull
     */
    private $code;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Region", mappedBy="countries")
     */
    private $regions;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\CountryState", mappedBy="country")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $states;

    public function __toString() {
        return 'Country => { Id = '.$this->getId().', Name = "'.$this->getName().'" }';
    }

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
     * Set code
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setRegions($value)
    {
        $this->regions = $value;
    }

    public function getRegions()
    {
        return $this->regions;
    }

    public function setStates($value)
    {
        $this->states = $value;
    }

    public function getStates()
    {
        return $this->states;
    }
}
