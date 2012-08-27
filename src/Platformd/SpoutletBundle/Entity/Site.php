<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\Site
 *
 * @ORM\Table(name="pd_site")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\SiteRepository")
 */
class Site
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
     * @var string $name
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $name;

    /**
     * @var string $defaultLocale
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $defaultLocale;

    /**
     * @var string $subDomain
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $subDomain;

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
     * Set deafultLocale
     *
     * @param string $deafultLocale
     */
    public function setDefaultLocale($deafultLocale)
    {
        $this->deafultLocale = $deafultLocale;
    }

    /**
     * Get deafultLocale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->deafultLocale;
    }

    /**
     * Set subDomain
     *
     * @param string $subDomain
     */
    public function setSubDomain($subDomain)
    {
        $this->subDomain = $subDomain;
    }

    /**
     * Get deafultLocale
     *
     * @return string
     */
    public function getSubDomain()
    {
        return $this->subDomain;
    }
}
