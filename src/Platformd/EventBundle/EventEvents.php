<?php

namespace Platformd\EventBundle;

/**
 * All Events dispatched by the Event Bundle
 */
final class EventEvents
{
    /**
     * The awa.event.create event is thrown each time an event gets created
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_CREATE = 'awa.event.create';

    /**
     * The awa.event.update event is thrown each time an event gets updated
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_UPDATE = 'awa.event.update';
}
