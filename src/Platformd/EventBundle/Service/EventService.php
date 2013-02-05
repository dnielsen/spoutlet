<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Repository\EventRepository,
    Platformd\EventBundle\Entity\Event,
    Platformd\EventBundle\Event\EventEvent,
    Platformd\EventBundle\EventEvents
;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Knp\MediaBundle\Util\MediaUtil;

class EventService
{
    /**
     * @var EventRepository
     */
    protected $repository;

    /**
     * @var MediaUtil
     */
    protected $mediaUtil;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function __construct(
        EventRepository $repository,
        MediaUtil $mediaUtil,
        EventDispatcher $dispatcher
    )
    {
        $this->repository   = $repository;
        $this->mediaUtil    = $mediaUtil;
        $this->dispatcher   = $dispatcher;
    }

    /**
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function createEvent(Event $event)
    {
        $this->handleMedia($event);

        $this->repository->saveEvent($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_CREATE, $event);
    }

    /**
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function updateEvent(Event $event)
    {
        $this->handleMedia($event);

        $this->repository->saveEvent($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_UPDATE, $event);
    }

    /**
     * Finds events by criteria
     *
     * @param $criteria
     * @return array
     */
    public function findBy($criteria)
    {
        return $this->repository->findBy($criteria);
    }

    /**
     * Find one event by criteria
     *
     * @param $criteria
     * @return object
     */
    public function findOneby($criteria)
    {
        return $this->repository->findOneBy($criteria);
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
    }
}
