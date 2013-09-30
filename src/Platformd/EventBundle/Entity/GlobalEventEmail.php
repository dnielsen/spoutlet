<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Entity\MassEmail;

/**
 * Platformd\EventBundle\Entity\GlobalEventEmail
 *
 * @ORM\Table(name="global_event_email")
 * @ORM\Entity(repositoryClass="Platformd\EventBundle\Repository\GlobalEventEmailRepository")
 */
class GlobalEventEmail extends MassEmail
{
    /**
     * Email recipients
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable(name="global_event_email_recipient")
     */
    protected $recipients;

    /**
     * Group Event linked to Email
     *
     * @var Platformd\EventBundle\Entity\GlobalEvent
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GlobalEvent")
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
     * @param Platformd\EventBundle\Entity\GlobalEvent $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    public function getEmailType()
    {
        return 'Global Event Mass Email';
    }

    public function getLinkedEntityClass()
    {
        return 'EventBundle:GlobalEvent';
    }

    public function getLinkedEntity()
    {
        return $this->event;
    }

    public function getLinkedEntityAllRecipientsField()
    {
        return 'attendees';
    }
}
