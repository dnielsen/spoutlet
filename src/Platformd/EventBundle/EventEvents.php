<?php

namespace Platformd\EventBundle;

/**
 * All Events dispatched by the Event Bundle
 */
final class EventEvents
{
    /**
     * The platformd.event.create event is thrown each time an event gets created
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_CREATE = 'platformd.event.create';

    /**
     * The platformd.event.update event is thrown each time an event gets updated
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_UPDATE = 'platformd.event.update';

    /**
     * The platformd.event.approve event is thrown each time an event gets approved
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_APPROVE = 'platformd.event.approve';
}
