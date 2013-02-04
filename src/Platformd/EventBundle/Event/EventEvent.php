<?php

namespace Platformd\EventBundle\Event;

use Symfony\Component\EventDispatcher\Event as BaseEvent;

use Platformd\EventBundle\Entity\Event;

class EventEvent extends BaseEvent
{
    /* @var Event */
    private $event;

    /**
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function __construct(Event $event)
    {
        $this->event     = $event;
    }

    /**
     * @return \Platformd\EventBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }
}
