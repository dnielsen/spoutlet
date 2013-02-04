<?php

namespace Platformd\SpoutletBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\UserBundle\Entity\User;

class GroupEvent extends Event
{
    protected $group;
    protected $user;

    public function __construct(
        Group $group,
        User $user
    )
    {
        $this->group    = $group;
        $this->user     = $user;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function getUser()
    {
        return $this->user;
    }
}
