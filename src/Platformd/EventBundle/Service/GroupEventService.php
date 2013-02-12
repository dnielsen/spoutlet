<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\EventBundle\Entity\Event,
    Platformd\EventBundle\Entity\GroupEventTranslation,
    Platformd\EventBundle\Event\EventEvent,
    Platformd\EventBundle\EventEvents,
    Platformd\SpoutletBundle\Entity\Group,
    Platformd\UserBundle\Entity\User
;

use Doctrine\Common\Collections\ArrayCollection;

use DateTime;

class GroupEventService extends EventService
{
    public function cloneGroupEvent(GroupEvent $groupEvent)
    {
        $clonedGroupEvent = new GroupEvent($groupEvent->getGroup());

        $clonedGroupEvent->setContent($groupEvent->getContent());
        $clonedGroupEvent->setUser($groupEvent->getUser());
        $clonedGroupEvent->setBannerImage($groupEvent->getBannerImage());
        $clonedGroupEvent->setRegistrationOption($groupEvent->getRegistrationOption());
        $clonedGroupEvent->setPublished($groupEvent->getPublished());
        $clonedGroupEvent->setOnline($groupEvent->getOnline());
        $clonedGroupEvent->setStartsAt(new DateTime());
        $clonedGroupEvent->setEndsAt(new DateTime());
        $clonedGroupEvent->setTimezone($groupEvent->getTimezone());
        $clonedGroupEvent->setDisplayTimezone($groupEvent->getDisplayTimezone());
        $clonedGroupEvent->setGame($groupEvent->getGame());
        $clonedGroupEvent->setExternalUrl($groupEvent->getExternalUrl());
        $clonedGroupEvent->setLocation($groupEvent->getLocation());
        $clonedGroupEvent->setAddress($groupEvent->getAddress());
        $clonedGroupEvent->setCreatedAt($groupEvent->getCreatedAt());
        $clonedGroupEvent->setPrivate($groupEvent->getPrivate());

        $clonedGroupEvent->setTranslations(new ArrayCollection());
        foreach ($groupEvent->getTranslations() as $translation) {
            $clonedGroupEvent->getTranslations()->add($this->cloneTranslation($translation, $clonedGroupEvent));
        }

        $clonedGroupEvent->setSites(new ArrayCollection);
        foreach($groupEvent->getSites() as $site) {
            $clonedGroupEvent->getSites()->add($site);
        }

        return $clonedGroupEvent;
    }

    protected function cloneTranslation(GroupEventTranslation $translation, GroupEvent $groupEvent)
    {
        $clonedGroupEventTranslation = clone($translation);

        $clonedGroupEventTranslation->setId(null);
        $clonedGroupEventTranslation->setTranslatable($groupEvent);

        return $clonedGroupEventTranslation;
    }

    public function findUpcomingEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        return $this->repository->findUpcomingEventsForGroupMostRecentFirst($group, $limit);
    }

    public function findPastEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        return $this->repository->findPastEventsForGroupMostRecentFirst($group, $limit);
    }

    public function findUpcomingEventsForUser(User $user, $whereIsOrganizer = false)
    {
        return $this->repository->getUpcomingEventListForUser($user, $whereIsOrganizer);
    }

    public function findPastEventsForUser(User $user)
    {
        return $this->repository->getPastEventListForUser($user);
    }

    /**
     * Retrieves all Events pending approval for a certain group
     *
     * @param \Platformd\SpoutletBundle\Entity\Group $group
     */
    public function getPendingApprovalEventsForGroup(Group $group)
    {
        return $this->repository->getPendingApprovalEventsForGroup($group);
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
