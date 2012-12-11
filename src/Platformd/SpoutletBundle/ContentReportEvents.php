<?php

namespace Platformd\SpoutletBundle;

final class ContentReportEvents
{
    /**
     * The awa.content.report event is thrown each time a piece of content is reported
     *
     * The event listener receives a Platformd\SpoutletBundle\Event\ContentReportEvent
     * instance.
     *
     * @var string
     */
    const REPORT = 'awa.content.report';

    /**
     * The awa.content.reinstate event is thrown each time a piece of content is reinstated
     *
     * The event listener receives a Platformd\SpoutletBundle\Event\ContentReportEvent
     * instance.
     *
     * @var string
     */
    const REINSTATE = 'awa.content.reinstate';
}
