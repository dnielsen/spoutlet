<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\EventBundle\Entity\GroupEventEmail
 *
 * @ORM\Table(name="group_event_email")
 * @ORM\Entity
 */
class GroupEventEmail extends EventEmail
{
    /**
     * Email recipients
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable(name="group_event_email_recipient")
     */
    protected $recipients;

    /**
     * Group Event linked to Email
     *
     * @var Platformd\EventBundle\Entity\GroupEvent
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $groupEvent;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
    }

    /**
     * @return Platformd\EventBundle\Entity\GroupEvent
     */
    public function getGroupEvent()
    {
        return $this->groupEvent;
    }

    /**
     * @param Platformd\EventBundle\Entity\GroupEvent $groupEvent
     */
    public function setGroupEvent($groupEvent)
    {
        $this->groupEvent = $groupEvent;
    }
}
