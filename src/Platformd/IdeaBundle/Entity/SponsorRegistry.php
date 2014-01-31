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
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group", inversedBy="sponsorRegistrations", cascade={"persist"})
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent", inversedBy="sponsorRegistrations", cascade={"persist"})
     */
    protected $event;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\IdeaBundle\Entity\Sponsor", inversedBy="sponsorRegistrations", cascade={"persist"})
     */
    protected $sponsor;

    /**
     * @ORM\Column(type="string")
     */
    protected $level;

    /**
     * Constructor
     */
    public function __construct($group = null, $event = null, $sponsor = null, $level = null)
    {
        if ($group) {
            $this->group = $group;
        }
        elseif ($event) {
            $this->event = $event;
        }

        if ($sponsor) {
            $this->sponsor = $sponsor;
        }
        if ($level) {
            $this->level = $level;
        }
    }

    public function getId()
    {
        return $this->id;
    }
    public function setEvent($event)
    {
        $this->event = $event;
    }
    public function getEvent()
    {
        return $this->event;
    }
    public function setGroup($group)
    {
        $this->group = $group;
    }
    public function getGroup()
    {
        return $this->group;
    }
    public function setSponsor($sponsor)
    {
        $this->sponsor = $sponsor;
    }
    public function getSponsor()
    {
        return $this->sponsor;
    }
    public function setLevel($level)
    {
        $this->level = $level;
    }
    public function getLevel()
    {
        return $this->level;
    }
} 