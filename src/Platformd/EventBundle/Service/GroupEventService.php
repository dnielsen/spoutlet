<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\EventBundle\Event\EventEvent,
    Platformd\EventBundle\EventEvents,
    Platformd\SpoutletBundle\Entity\Group
;

class GroupEventService extends EventService
{
    public function findUpcomingEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        return $this->repository->findUpcomingEventsForGroupMostRecentFirst($group, $limit);
    }

    public function findPastEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        return $this->repository->findPastEventsForGroupMostRecentFirst($group, $limit);
    }

    public function findEventsForUser(User $user, $whereIsOrganizer = false)
    {
        return $this->repository->getEventListForUser($user, $whereIsOrganizer);
    }

    public function findPastEventsForUser(User $user)
    {
        return $this->repository->getPastEventListForUser($user);
    }
}
