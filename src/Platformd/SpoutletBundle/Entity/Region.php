<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\Region
 *
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\RegionRepository")
 * @ORM\Table(name="region")
 */
class Region
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
     * @ORM\OneToMany(targetEntity="Country", mappedBy="region")
     * @ORM\OrderBy({"code" = "DESC"})
     *
     * @var ArrayCollection $countries
     */
    private $countries;

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

    /**
     * Set countries
     *
     * @param string $countries
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;
    }

    /**
     * Get countries
     *
     * @return string
     */
    public function getCountries()
    {
        return $this->countries;
    }
}
