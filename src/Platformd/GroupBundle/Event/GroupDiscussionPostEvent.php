<?php

namespace Platformd\GroupBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Platformd\GroupBundle\Entity\GroupDiscussionPost;

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
