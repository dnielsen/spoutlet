<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\EventBundle\Entity\Event,
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

    /**
     * Saves Banner to image farm
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    protected function handleMedia(Event $event) {
        if (!$this->mediaUtil->persistRelatedMedia($event->getBannerImage())) {
            $event->setBannerImage(null);
        }

        foreach($event->getTranslations() as $translation) {
            if (!$this->mediaUtil->persistRelatedMedia($translation->getBannerImage())) {
                $translation->setBannerImage(null);
            }
        }
    }
}
