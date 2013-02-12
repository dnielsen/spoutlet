<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Repository\EventRepository,
    Platformd\EventBundle\Entity\Event,
    Platformd\UserBundle\Entity\User,
    Platformd\EventBundle\Event\EventEvent,
    Platformd\EventBundle\Event\RegistrationEvent,
    Platformd\EventBundle\EventEvents,
    Platformd\SpoutletBundle\Entity\Group
;

use Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Acl\Model\MutableAclProviderInterface as aclProvider,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Permission\MaskBuilder
;

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

    /**
     * @var AclProvider
     */
    protected $aclProvider;

    public function __construct(
        EventRepository $repository,
        MediaUtil $mediaUtil,
        EventDispatcher $dispatcher,
        AclProvider $aclProvider
    )
    {
        $this->repository   = $repository;
        $this->mediaUtil    = $mediaUtil;
        $this->dispatcher   = $dispatcher;
        $this->aclProvider  = $aclProvider;
    }

    /**
     * Create and event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function createEvent(Event $event)
    {
        $this->handleMedia($event);

        $this->repository->saveEvent($event);

        // ACLs
        $objectIdentity = ObjectIdentity::fromDomainObject($event);
        $acl = $this->aclProvider->createAcl($objectIdentity);
        $securityIdentity = UserSecurityIdentity::fromAccount($event->getUser());

        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $this->aclProvider->updateAcl($acl);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_CREATE, $event);
    }

    /**
     * Update an event
     *
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
     * Approve an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function approveEvent(Event $event)
    {
        $event->setApproved(true);

        $this->updateEvent($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_APPROVE, $event);
    }

    /**
     * Cancel an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function cancelEvent(Event $event)
    {
        $event->setActive(false);

        $this->updateEvent($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_CANCEL, $event);
    }

    /**
     * Activate an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function activateEvent(Event $event)
    {
        $event->setActive(true);

        $this->updateEvent($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_ACTIVATE, $event);
    }

    /**
     * Publish event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function publishEvent(Event $event)
    {
        $event->setPublished(true);

        $this->repository->saveEvent($event);
    }

    /**
     * Unpublish event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function unpublishEvent(Event $event)
    {
        $event->setPublished(false);

        $this->repository->saveEvent($event);
    }

    /**
     * Registers a user to an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function register(Event $event, User $user)
    {
        $event->getAttendees()->add($user);

        $this->repository->saveEvent($event);

        // We dispatch an event for further tasks
        $event = new RegistrationEvent($event, $user);
        $this->dispatcher->dispatch(EventEvents::EVENT_UNREGISTER, $event);
    }

    /**
     * Unregisters a user to an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function unregister(Event $event, User $user)
    {
        $event->getAttendees()->removeElement($user);

        $this->repository->saveEvent($event);

        // We dispatch an event for further tasks
        $event = new RegistrationEvent($event, $user);
        $this->dispatcher->dispatch(EventEvents::EVENT_UNREGISTER, $event);
    }

    /**
     * Finds one event by id
     *
     * @param $id
     * @return \Platformd\EventBundle\Entity\Event
     */
    public function find($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Finds events by criteria
     *
     * @param $criteria
     * @param array $orderBy
     * @param $limit
     * @param $offset
     * @return array
     */
    public function findBy($criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Find one event by criteria
     *
     * @param $criteria
     * @param array $orderBy
     * @return object
     */
    public function findOneby($criteria, array $orderBy = null)
    {
        return $this->repository->findOneBy($criteria, $orderBy);
    }

    /**
     * Retrieves all events created by a user
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @return array
     */
    public function findAllOwnedEventsForUser(User $user)
    {
        return $this->repository->getAllOwnedEventsForUser($user);
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

    /**
     * Gets the count of attendees for an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function getAttendeeCount($event)
    {
        return $this->repository->getAttendeeCount($event);
    }

    /**
     * Returns whether a user is registered to attend an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function isUserAttending(Event $event, User $user)
    {
        return $this->repository->isUserAttending($event, $user);
    }
}
