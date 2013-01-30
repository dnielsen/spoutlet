<?php

namespace Platformd\SpoutletBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Platformd\UserBundle\Entity\User;

class ContentReportEvent extends Event
{
    protected $reporter;
    protected $content;

    public function __construct($content, User $reporter)
    {
        $this->reporter = $reporter;
        $this->content = $content;
    }

    public function getReporter()
    {
        return $this->getReporter();
    }

    public function getContent()
    {
        return $this->content;
    }
}
