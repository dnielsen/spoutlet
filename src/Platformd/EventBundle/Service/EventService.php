<?php

namespace Platformd\EventBundle\Service;

use Platformd\EventBundle\Repository\EventRepository,
    Platformd\EventBundle\Entity\Event,
    Platformd\UserBundle\Entity\User,
    Platformd\EventBundle\Entity\GroupEvent,
    Platformd\SpoutletBundle\Entity\MassEmail,
    Platformd\EventBundle\Entity\GroupEventEmail,
    Platformd\EventBundle\Entity\GlobalEventEmail,
    Platformd\EventBundle\Event\EventEvent,
    Platformd\EventBundle\Event\RegistrationEvent,
    Platformd\EventBundle\EventEvents,
    Platformd\EventBundle\Entity\EventRsvpAction,
    Platformd\EventBundle\Entity\GlobalEventRsvpAction,
    Platformd\EventBundle\Entity\GroupEventRsvpAction,
    Platformd\GroupBundle\Entity\Group,
    Platformd\SpoutletBundle\Entity\Site,
    Platformd\SpoutletBundle\Model\EmailManager,
    Platformd\SpoutletBundle\Model\Translator,
    Platformd\CEVOBundle\Api\ApiException
;

use Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Acl\Model\MutableAclProviderInterface as aclProvider,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Permission\MaskBuilder,
    Symfony\Component\Routing\RouterInterface
;

use Knp\MediaBundle\Util\MediaUtil;

use DateTime;

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

    /**
     * @var EmailManager
     */
    protected $emailManager;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Cevo Api
     */
    protected $cevoApi;

    public function __construct(
        EventRepository $repository,
        MediaUtil $mediaUtil,
        EventDispatcher $dispatcher,
        AclProvider $aclProvider,
        EmailManager $emailManager,
        RouterInterface $router,
        Translator $translator,
        $cevoApi
    )
    {
        $this->repository    = $repository;
        $this->mediaUtil     = $mediaUtil;
        $this->dispatcher    = $dispatcher;
        $this->aclProvider   = $aclProvider;
        $this->emailManager  = $emailManager;
        $this->router        = $router;
        $this->translator    = $translator;
        $this->cevoApi       = $cevoApi;
    }

    /**
     * Create and event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function createEvent(Event $event)
    {
        $this->handleMedia($event);

        if ($event instanceof GroupEvent) {

            try {
                $response = $this->cevoApi->GiveUserXp('addcontent', $event->getUser()->getCevoUserId());
            } catch (ApiException $e) {

            }

            $this->register($event, $event->getUser());

            if ($event->getExternalUrl()) {
                $event->setPrivate(false);
            }

        } else {
            $event->setApproved(true);

            if ($event->getExternalUrl()) {
                $event->setRegistrationOption(GroupEvent::REGISTRATION_3RD_PARTY);
            } else {
                $event->setRegistrationOption(GroupEvent::REGISTRATION_ENABLED);
            }
        }

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

        if ($event instanceof GroupEvent) {
            if ($event->getExternalUrl()) {
                $event->setPrivate(false);
            }
        } else {
            if ($event->getExternalUrl()) {
                $event->setRegistrationOption(GroupEvent::REGISTRATION_3RD_PARTY);
            } else {
                $event->setRegistrationOption(GroupEvent::REGISTRATION_ENABLED);
            }
        }

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
     * Delete an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function deleteEvent(Event $event)
    {
        $event->setDeleted(true);

        $this->updateEvent($event);

        // We dispatch an event for further tasks
        $event = new EventEvent($event);
        $this->dispatcher->dispatch(EventEvents::EVENT_DELETE, $event);
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
        if ($event->getId() && $this->repository->isUserAttending($event, $user)) {
            return;
        }

        if ($event->getUser() != $user) {
            try {
                $response = $this->cevoApi->GiveUserXp('joinevent', $user->getCevoUserId());
            } catch (ApiException $e) {

            }
        }

        $rsvpAction = ($event instanceof GroupEvent) ? new GroupEventRsvpAction() : new GlobalEventRsvpAction();

        $rsvpAction->setUser($user);
        $rsvpAction->setEvent($event);

        if ($event->getPrivate()) {
            $rsvpAction->setAttendance(EventRsvpAction::ATTENDING_PENDING);
        }
        else {
            $rsvpAction->setAttendance(EventRsvpAction::ATTENDING_YES);
        }

        $rsvpAction->setRsvpAt(new DateTime('now'));

        $event->getRsvpActions()->add($rsvpAction);
        $event->getAttendees()->add($user);
        $event->updateAttendeeCount();

        $this->repository->saveEvent($event);

        // We dispatch an event for further tasks
        $e = new RegistrationEvent($event, $user);
        $this->dispatcher->dispatch(EventEvents::EVENT_REGISTER, $e);
    }

    /**
     * Unregisters a user to an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function unregister(Event $event, User $user, $rejected = false)
    {
        if ($event->getId() && !$this->repository->isUserAttending($event, $user)) {
            return;
        }

        $rsvpAction = ($event instanceof GroupEvent) ? new GroupEventRsvpAction() : new GlobalEventRsvpAction();

        $rsvpAction->setUser($user);
        $rsvpAction->setEvent($event);
        if ($rejected) {
            $rsvpAction->setAttendance(EventRsvpAction::ATTENDING_REJECTED);
        }
        else {
            $rsvpAction->setAttendance(EventRsvpAction::ATTENDING_NO);
        }
        $rsvpAction->setRsvpAt(new DateTime('now'));

        $event->getRsvpActions()->add($rsvpAction);
        $event->getAttendees()->removeElement($user);
        $event->updateAttendeeCount(-1);

        $this->repository->saveEvent($event);

        // We dispatch an event for further tasks
        $e = new RegistrationEvent($event, $user);
        $this->dispatcher->dispatch(EventEvents::EVENT_UNREGISTER, $e);
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
     * Returns whether a user is registered to attend an event
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function isUserAttending(Event $event, $user)
    {
        return $user instanceof User ? $this->repository->isUserAttending($event, $user) : false;
    }

    public function getUserRsvpStatus(Event $event, User $user)
    {
        return $this->repository->getUserRsvpStatus($event, $user);
    }

    public function saveEmail(MassEmail $email)
    {
        $this->repository->saveEmail($email);
    }

    public function sendReminderEmail(Event $event, $site)
    {
        if ($event instanceof GroupEvent) {
            $email = new GroupEventEmail();
        } else {
            $email = new GlobalEventEmail();
        }

        $recipients = array();

        foreach ($event->getAttendees() as $attendee) {
            $recipients[] = $attendee;
        }

        $email->setEvent($event);
        $email->setRecipients($recipients);

        $emailType  = $email instanceof GroupEventEmail ? "Group Event Reminder Email" : $email instanceof GlobalEventEmail ? "Global Event Reminder Email" : "Event Reminder Email";

        $sendCount = 0;

        $eventSite = $event->getSites()->first();

        foreach ($email->getRecipients() as $recipient) {
            $emailTo = $recipient->getEmail();

            $locale = $recipient->getLocale() ?: 'en';

            $subject = $this->translator->themeTrans('platformd.event.email.event_reminder.title', $eventSite->getTheme(), array(
                '%eventName%' => $event->getName(),
            ), $locale);

            $message = nl2br($this->translator->themeTrans('platformd.event.email.event_reminder.message', $eventSite->getTheme(), array(
                '%eventName%'       => $event->getName(),
                '%dateString%'      => $event->getDateRangeString(),
                '%timeString%'      => $event->getStartsAt()->format('g:i A'),
                '%timezone%'        => $event->getTimezoneString(),
                '%location%'        => $event->getOnline() ? 'Online' : $event->getHtmlFormattedAddress(),
                '%eventUrl%'        => $this->router->generate($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
                '%organizerUrl%'    => $event->getUser()->getAccountLink($locale),
                '%organizerName%'   => $event->getUser()->getUsername(),
            ), $locale));

            $fromName = $site->getSiteFeatures()->getEmailFromName() ?: null;
            $fromEmail = $site->getSiteFeatures()->getAutomatedEmailAddress() ?: null;

            $this->emailManager->sendHtmlEmail($emailTo, $subject, str_replace('%username%', $recipient->getUsername(), $message), $emailType, $fromName, $fromEmail);
            $sendCount++;
        }

        $email->setSubject($subject);
        $email->setMessage($message);

        $this->repository->saveEmail($email);
    }

    public function findUpcomingEventsStartingDaysFromNow($days)
    {
        return $this->repository->findUpcomingEventsStartingDaysFromNow($days);
    }

    public function findUpcomingEventsForUser(User $user, $whereIsOrganizer = false)
    {
        return $this->repository->getUpcomingEventListForUser($user, $whereIsOrganizer);
    }

    public function findPastEventsForUser(User $user, $whereIsOrganizer = false)
    {
        return $this->repository->getPastEventListForUser($user, $whereIsOrganizer);
    }

    public function getAttendeeList($event)
    {
        return $this->repository->getAttendeeList($event);
    }

    public function findUpcomingEventsForSite(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager = null, $published = true)
    {
        return $this->repository->findUpcomingEventsForSitePaged($site, $maxPerPage, $currentPage, $pager, $published);
    }

    public function findPastEventsForSite(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager = null, $published = true)
    {
        return $this->repository->findPastEventsForSitePaged($site, $maxPerPage, $currentPage, $pager, $published);
    }

    public function findUpcomingEventsForSiteLimited(Site $site, $limit=6, $published = true)
    {
        return $this->repository->findUpcomingEventsForSiteLimited($site, $limit, $published);
    }

    public function eventCompare($a, $b) {

        if ($a->getStartsAt() == $b->getStartsAt()) {
            return 0;
        }
        return ($a->getStartsAt() < $b->getStartsAt()) ? -1 : 1;

    }
}
