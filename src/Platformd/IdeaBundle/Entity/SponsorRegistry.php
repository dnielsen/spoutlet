<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Platformd\EventBundle\Entity\GroupEvent;
use Platformd\EventBundle\Entity\GlobalEvent;

/**
 * @ORM\Table(name="sponsor_registry")
 * @ORM\Entity()
 */
class SponsorRegistry
{
    const VENUE    = 0;
    const PLATINUM = 1;
    const GOLD     = 2;
    const SILVER   = 3;
    const BRONZE   = 4;
    const OTHER    = 5;

    const STATUS_RECOMMENDED = 'recommended';
    const STATUS_WATCHING    = 'watching';
    const STATUS_CONSIDERING = 'considering';
    const STATUS_SPONSORING  = 'sponsoring';

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
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GlobalEvent", inversedBy="sponsorRegistrations", cascade={"persist"})
     */
    protected $global_event;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\IdeaBundle\Entity\Sponsor", inversedBy="sponsorRegistrations", cascade={"persist"})
     */
    protected $sponsor;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $level = self::OTHER;

    /**
     * @ORM\Column(type="string")
     */
    protected $status = self::STATUS_SPONSORING;


    /**
     * Constructor
     */
    public function __construct($group = null, $event = null, $sponsor = null, $level = null, $status = null)
    {
        if ($group) {
            $this->group = $group;
        } elseif ($event) {
            if ($event instanceof GroupEvent) {
                $this->event = $event;
            } elseif ($event instanceof GlobalEvent) {
                $this->global_event = $event;
            }            
        }

        if ($sponsor) {
            $this->sponsor = $sponsor;
        }
        if ($level) {
            $this->level = $level;
        }
        if ($status) {
            $this->status = $status;
        }
    }

    public function getId()
    {
        return $this->id;
    }
    public function setEvent($event)
    {
        if ($event instanceof GroupEvent) {
            $this->event = $event;
        } elseif ($event instanceof GlobalEvent) {
            $this->global_event = $event;
        }
    }
    public function getEvent()
    {
        if ($this->event) {
            return $this->event;
        } elseif ($this->global_event) {
            return $this->global_event;
        }
    }
    public function getSponsoredObj()
    {
        if ($this->group) {
            return $this->group;
        } elseif ($this->event) {
            return $this->event;
        } elseif ($this->global_event) {
            return $this->global_event;
        }
    }
    public function getSponsoredObjOwner()
    {
        if ($this->group) {
            return $this->group->getOwner();
        } elseif ($this->event) {
            return $this->event->getUser();
        } elseif ($this->global_event) {
            return $this->global_event->getUser();
        }
    }

    public function getScope()
    {
        if ($this->group) {
            return 'group';
        } elseif ($this->event) {
            return 'event';
        } elseif ($this->global_event) {
            return 'global_event';
        }
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
    public function setStatus($status)
    {
        $this->status = $status;
    }
    public function getStatus()
    {
        return $this->status;
    }
} 