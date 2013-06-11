<?php

namespace Platformd\EventBundle\Service;

use Platformd\SpoutletBundle\Entity\Site,
    Platformd\EventBundle\Entity\GlobalEvent,
    Platformd\EventBundle\Entity\GlobalEventTranslation,
    Platformd\GameBundle\Entity\Game,
    Platformd\UserBundle\Entity\User
;

use Doctrine\Common\Collections\ArrayCollection;

use DateTime;

class GlobalEventService extends EventService
{
    public function cloneGlobalEvent(GlobalEvent $globalEvent)
    {
        $clonedGlobalEvent = new GlobalEvent();

        $clonedGlobalEvent->setContent($globalEvent->getContent());
        $clonedGlobalEvent->setUser($globalEvent->getUser());
        $clonedGlobalEvent->setBannerImage($globalEvent->getBannerImage());
        $clonedGlobalEvent->setRegistrationOption($globalEvent->getRegistrationOption());
        $clonedGlobalEvent->setPublished($globalEvent->getPublished());
        $clonedGlobalEvent->setOnline($globalEvent->getOnline());
        $clonedGlobalEvent->setStartsAt(new DateTime());
        $clonedGlobalEvent->setEndsAt(new DateTime());
        $clonedGlobalEvent->setTimezone($globalEvent->getTimezone());
        $clonedGlobalEvent->setDisplayTimezone($globalEvent->getDisplayTimezone());
        $clonedGlobalEvent->setGame($globalEvent->getGame());
        $clonedGlobalEvent->setExternalUrl($globalEvent->getExternalUrl());
        $clonedGlobalEvent->setLocation($globalEvent->getLocation());
        $clonedGlobalEvent->setAddress1($globalEvent->getAddress1());
        $clonedGlobalEvent->setAddress2($globalEvent->getAddress2());
        $clonedGlobalEvent->setCreatedAt($globalEvent->getCreatedAt());

        $clonedGlobalEvent->setTranslations(new ArrayCollection());
        foreach ($globalEvent->getTranslations() as $translation) {
            $clonedGlobalEvent->getTranslations()->add($this->cloneTranslation($translation, $globalEvent));
        }

        $clonedGlobalEvent->setSites(new ArrayCollection);
        foreach($globalEvent->getSites() as $site) {
            $clonedGlobalEvent->getSites()->add($site);
        }

        return $clonedGlobalEvent;
    }

    protected function cloneTranslation(GlobalEventTranslation $translation, GlobalEvent $globalEvent)
    {
        $clonedGroupEventTranslation = clone($translation);

        $clonedGroupEventTranslation->setId(null);
        $clonedGroupEventTranslation->setTranslatable($globalEvent);

        return $clonedGroupEventTranslation;
    }

    public function findOneBySlugForSite($slug, Site $site)
    {
        return $this->repository->findOneBySlugForSite($slug, $site);
    }

    public function findEventsForGamePage(Site $site, Game $game, $published = true)
    {
        return $this->repository->findEventsForGamePage($site, $game, $published);
    }

    public function findGlobalEventStats(array $data = array())
    {
        return $this->repository->findGlobalEventStats($data);
    }

    public function getAllEventsUserIsAttending(User $user)
    {
        return $this->repository->getAllEventsUserIsAttending($user);
    }

    public function findAllForSite($site)
    {
        return $this->repository->findAllForSite($site);
    }

    public function findAllForSiteWithLimit($site, $limit = 9)
    {
        return $this->repository->findAllForSiteWithLimit($site, $limit);
    }
}
