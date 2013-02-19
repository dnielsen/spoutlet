<?php

namespace Platformd\EventBundle\Service;

use Platformd\SpoutletBundle\Entity\Site,
    Platformd\EventBundle\Entity\GlobalEvent,
    Platformd\EventBundle\Entity\GlobalEventTranslation,
    Platformd\GameBundle\Entity\Game
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
        $clonedGlobalEvent->setAddress($globalEvent->getAddress());
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

    public function findUpcomingEventsForSite(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager = null, $published = true)
    {
        return $this->repository->findUpcomingEventsForSite($site, $maxPerPage, $currentPage, $pager, $published);
    }

    public function findPastEventsForSite(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager = null, $published = true)
    {
        return $this->repository->findPastEventsForSite($site, $maxPerPage, $currentPage, $pager, $published);
    }

    public function findEventsForGamePage(Site $site, Game $game, $published = true)
    {
        return $this->repository->findEventsForGamePage($site, $game, $published);
    }

    public function findGlobalEventMetrics($filter)
    {
        return $this->repository->findGlobalEventMetrics($filter);
    }

    public function findGlobalEventStats(array $data = array())
    {
        return $this->repository->findGlobalEventStats($data);
    }
}
