<?php

namespace Platformd\SpoutletBundle\Entity;

use DateTime,
    DateTimeZone
;

class DiscussionFindWrapper
{
    private $discussionName;
    private $deleted;
    private $sites;
    private $from;
    private $thru;

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDiscussionName($discussionName)
    {
        $this->discussionName = $discussionName;
    }

    public function getDiscussionName()
    {
        return $this->discussionName;
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
}
