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
    protected $event;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
    }

    /**
     * @return Platformd\EventBundle\Entity\GroupEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param Platformd\EventBundle\Entity\GroupEvent $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }
}
