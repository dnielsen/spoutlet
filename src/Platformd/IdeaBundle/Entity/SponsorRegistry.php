<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 12/4/13
 * Time: 12:22 PM
 */

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Table(name="sponsor_registry")
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\SponsorRegistryRepository")
 */
class SponsorRegistry {
    const SPONSORSHIP_LEVEL_PLATINUM = "platinum";
    const SPONSORSHIP_LEVEL_GOLD = "gold";
    const SPONSORSHIP_LEVEL_SILVER = "silver";
    const SPONSORSHIP_LEVEL_BRONZE = "bronze";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $scope;

    /**
     * @ORM\Column(type="integer")
     */
    protected $containerId;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\Sponsor", mappedBy="sponsorRegistration", cascade={"persist"})
     */
    protected $sponsors;

    /**
     * @ORM\Column(type="integer")
     */
    protected $level;

    /**
     * Constructor
     */
    public function __construct($container)
    {
        $this->sponsors = new ArrayCollection();
        $this->containerId = $container->getId();

        $className = get_class($container);
        $this->scope = preg_replace('/\w+\\\\(\w+Bundle)\\\\Entity\\\\(\w+)/', "$1:$2", $className);
    }

    /**
     * @return mixed
     */
    public function getContainerId()
    {
        return $this->containerId;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return mixed
     */
    public function getSponsors()
    {
        return $this->sponsors;
    }

    public function addSponsor($sponsor) {
        $this->sponsors->add($sponsor);
    }

    public function getLevel() {
        return $this->level;
    }

    public function setLevel($level) {
        $this->level = $level;
    }
} 