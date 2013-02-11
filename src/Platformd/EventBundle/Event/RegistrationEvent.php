<?php

namespace Platformd\EventBundle\Event;

use Symfony\Component\EventDispatcher\Event as BaseEvent;

use Platformd\EventBundle\Entity\Event,
    Platformd\UserBundle\Entity\User
;

class RegistrationEvent extends BaseEvent
{
    /* @var Event */
    private $event;

    /* @var User */
    private $user;

    /**
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function __construct(Event $event, User $user)
    {
        $this->event    = $event;
        $this->user     = $user;
    }

    /**
     * @return \Platformd\EventBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
