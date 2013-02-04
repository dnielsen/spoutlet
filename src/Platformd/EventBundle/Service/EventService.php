<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Repository\EventRepository,
    Platformd\EventBundle\Entity\Event,
    Platformd\EventBundle\Event\EventEvent,
    Platformd\EventBundle\EventEvents
;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Gaufrette\Filesystem;

abstract class EventService
{
    /**
     * @var EventRepository
     */
    protected $repository;

    protected $filesystem;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function __construct(
        EventRepository $repository,
        Filesystem $filesystem,
        EventDispatcher $dispatcher
    )
    {
        $this->repository   = $repository;
        $this->filesystem   = $filesystem;
        $this->dispatcher   = $dispatcher;
    }

    /**
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function createEvent(Event $event)
    {
        $this->repository->saveEvent($event);
        $this->updateBannerImage($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_CREATE, $event);
    }

    /**
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function updateEvent(Event $event)
    {
        $this->repository->saveEvent($event);
        $this->updateBannerImage($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_UPDATE, $event);
    }

    /**
     * Update an event's banner image
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    protected function updateBannerImage(Event $event)
    {
        $file = $event->getBannerImageFile();

        if (null == $file) {
            return;
        }

        $filename = sha1($event->getId().'-'.uniqid()).'.'.$file->guessExtension();
        // prefix repeated in BannerPathResolver
        $this->filesystem->write(Event::PREFIX_PATH_BANNER.$filename, file_get_contents($file->getPathname()));
        $event->setBannerImage($filename);
    }
}
