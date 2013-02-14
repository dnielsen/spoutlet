<?php

namespace Platformd\GroupBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Platformd\GroupBundle\Entity\GroupDiscussion;
use Platformd\UserBundle\Entity\User;

class GroupDiscussionEvent extends Event
{
    protected $groupDiscussion;
    protected $user;

    public function __construct(
        GroupDiscussion $groupDiscussion,
        User $user = null
    )
    {
        $this->groupDiscussion  = $groupDiscussion;
        $this->user             = $user;
    }

    public function getGroupDiscussion()
    {
        return $this->groupDiscussion;
    }

    public function getUser()
    {
        return $this->user;
    }
}
