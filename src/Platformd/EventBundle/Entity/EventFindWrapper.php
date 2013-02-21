<?php

namespace Platformd\EventBundle\Entity;

use DateTime,
    DateTimeZone
;

class EventFindWrapper
{
    private $eventName;
    private $sites;
    private $eventType;
    private $filter;

    public function setEventName($value)
    {
        $this->eventName = $value;
    }

    public function getEventName()
    {
        return $this->eventName;
    }

    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setEventType($value)
    {
        $this->eventType = $value;
    }

    public function getEventType()
    {
        return $this->eventType;
    }

    public function setFilter($value)
    {
        $this->filter = $value;
    }

    public function getFilter()
    {
        return $this->filter;
    }
}
