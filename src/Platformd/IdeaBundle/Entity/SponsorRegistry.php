<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="sponsor_registry")
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\SponsorRegistryRepository")
 */
class SponsorRegistry {
    const SPONSORSHIP_LEVEL_PLATINUM = "platinum";
    const SPONSORSHIP_LEVEL_GOLD     = "gold";
    const SPONSORSHIP_LEVEL_SILVER   = "silver";
    const SPONSORSHIP_LEVEL_BRONZE   = "bronze";

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
     * @ORM\OneToOne(targetEntity="Platformd\IdeaBundle\Entity\Sponsor", inversedBy="sponsorRegistration", cascade={"persist"})
     */
    protected $sponsor;

    /**
     * @ORM\Column(type="string")
     */
    protected $level;

    /**
     * Constructor
     */
    public function __construct($container)
    {
        $this->containerId = $container->getId();
        $className = get_class($container);

        $this->scope = preg_replace('/\w+\\\\(\w+Bundle)\\\\Entity\\\\(\w+)/', "$1:$2", $className);
    }

    public function getId()
    {
        return $this->id;
    }
    public function getScope()
    {
        return $this->scope;
    }
    public function getContainerId()
    {
        return $this->containerId;
    }

    public function getSponsors()
    {
        return $this->sponsor;
    }
    public function setSponsor($sponsor) {
        $this->sponsor = $sponsor;
    }

    public function getLevel() {
        return $this->level;
    }
    public function setLevel($level) {
        $this->level = $level;
    }
} 