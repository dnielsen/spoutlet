<?php

namespace Platformd\SpoutletBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Platformd\SpoutletBundle\Entity\GroupDiscussionPost;

class GroupDiscussionPostEvent extends Event
{
    protected $groupDiscussionPost;

    public function __construct(GroupDiscussionPost $groupDiscussionPost)
    {
        $this->groupDiscussionPost = $groupDiscussionPost;
    }

    public function getGroupDiscussionPost()
    {
        return $this->groupDiscussionPost;
    }
}