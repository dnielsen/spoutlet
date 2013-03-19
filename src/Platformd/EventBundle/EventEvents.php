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

    /**
     * The platformd.event.cancel event is thrown each time an event gets canceled
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_CANCEL = 'platformd.event.cancel';

    /**
     * The platformd.event.delete event is thrown each time an event gets deleted
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_DELETE = 'platformd.event.delete';

    /**
     * The platformd.event.activate event is thrown each time an event gets activated
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_ACTIVATE = 'platformd.event.activate';

    /**
     * The platformd.event.register event is thrown each time a user registers for an event
     *
     * The event listener receives a Platformd\EventBundle\Event\RegistrationEvent
     * instance.
     *
     * @var string
     */
    const EVENT_REGISTER = 'platformd.event.register';

    /**
     * The platformd.event.unregister event is thrown each time a user unregisters for an event
     *
     * The event listener receives a Platformd\EventBundle\Event\RegistrationEvent
     * instance.
     *
     * @var string
     */
    const EVENT_UNREGISTER = 'platformd.event.unregister';

    /**
     * The platformd.event.email event is thrown each time an event email is sent
     *
     * The event listener receives a Platformd\EventBundle\Event\EventEvent
     * instance.
     *
     * @var string
     */
    const EVENT_EMAIL = 'platformd.event.email';
}
