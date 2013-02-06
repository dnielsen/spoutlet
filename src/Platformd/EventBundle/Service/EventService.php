<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Repository\EventRepository,
    Platformd\EventBundle\Entity\Event,
    Platformd\UserBundle\Entity\User,
    Platformd\EventBundle\Event\EventEvent,
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
     * Saves Banner to image farm
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    protected function handleMedia(Event $event) {
        if (!$this->mediaUtil->persistRelatedMedia($event->getBannerImage())) {
            $event->setBannerImage(null);
        }


    }

    public function getAttendeeCount($event)
    {
        return $this->repository->getAttendeeCount($event);
    }

    /**
     * Retrieves all Events pending approval for a certain group
     *
     * @param \Platformd\SpoutletBundle\Entity\Group $group
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function getPendingApprovalEvents(Group $group, User $user)
    {
        return $this->repository->getPendingApprovalEvents($group, $user);
    }
}
