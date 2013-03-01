<?php

namespace Platformd\EventBundle\Entity;

use DateTime,
    DateTimeZone
;

class EventFindWrapper
{
    private $eventName;
    private $published;
    private $sites;
    private $from;
    private $thru;
    private $eventType;

    public function setPublished($value)
    {
        $this->published = $value;
    }

    public function getPublished()
    {
        return $this->published;
    }

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

    public function setFrom($from)
    {
        $this->from = $from;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setThru($thru)
    {
        $this->thru = $thru;
    }

    public function getThru()
    {
        return $this->thru;
    }

    public function setEventType($value)
    {
        $this->eventType = $value;
    }

    public function getEventType()
    {
        return $this->eventType;
    }
}
