<?php

namespace Platformd\SpoutletBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ContentReportEvent extends Event
{
    protected $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }
}
