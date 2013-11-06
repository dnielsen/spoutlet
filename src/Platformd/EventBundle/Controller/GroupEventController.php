<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\GroupBundle\Entity\Group,
    Platformd\GroupManager\Model\GroupManager
;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\EventBundle\Form\Type\EventType,
    Platformd\EventBundle\Form\Type\GroupEventType,
    Platformd\EventBundle\Service\EventService,
    Platformd\EventBundle\Entity\GroupEventTranslation,
    Platformd\EventBundle\Entity\GroupEventEmail,
    Platformd\UserBundle\Entity\RegistrationSource
;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
;

use Doctrine\Common\Collections\ArrayCollection;

use JMS\SecurityExtraBundle\Annotation\Secure;

use DateTime;

class GroupEventController extends Controller
{
    public function _groupEventUserInfoAction($id)
    {
        $event = $this->getGroupEventService()->find($id);

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
        }

        $user  = $this->getUser();
        $group = $event->getGroup();

        $isAttending   = $this->isGranted('ROLE_USER') ? $this->getGroupEventService()->isUserAttending($event, $user) : false;
        $isOwner       = $user == $event->getUser();
        $isGroupMember = $this->getGroupManager()->isMember($user, $group);
        $isApplicant   = $this->getGroupManager()->isApplicant($user, $group);
        $canJoin       = $this->getGroupManager()->isAllowedTo($user, $group, $this->getCurrentSite(), 'JoinEvent');
        $attendeeCount = $event->getAttendeeCount();

        $response = $this->render('EventBundle:GlobalEvent:_eventUserInfo.html.twig', array(
            'isAttending'   => $isAttending,
            'isOwner'       => $isOwner,
            'isGroupMember' => $isGroupMember,
            'isApplicant'   => $isApplicant,
            'canJoin'       => $canJoin,
            'attendeeCount' => $attendeeCount,
        ));

        $this->varnishCache($response, 1);

        return $response;
    }

    /**
     * @Secure(roles="ROLE_USER")
     */
    public function newAction($groupSlug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));
        $tagManager = $this->getTagManager();
        /** @var Group $group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$this->getGroupManager()->isAllowedTo($this->getUser(), $group, $this->getCurrentSite(), 'AddEvent')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        $existingEvents     = $this->getGroupEventService()->findAllOwnedEventsForUser($this->getUser());
        $importedGroupEvent = $this->getGroupEventService()->findOneBy(array('id' => $request->get('existing_event_select')));

        if ($importedGroupEvent) {
            return $this->redirect($this->generateUrl('group_event_new_import', array('groupSlug' => $group->getSlug(), 'eventId' => $importedGroupEvent->getId())));
        }

        $groupEvent = new GroupEvent($group);

        // We add translations by hand
        // TODO improve this
        $siteLocalesForTranslation = array('ja', 'zh', 'es');
        foreach ($siteLocalesForTranslation as $locale) {
            $site = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($locale);
            if ($site) {
                $groupEvent->addTranslation(new GroupEventTranslation($site, $groupEvent));
            }
        }

        $form = $this->createForm('groupEvent', $groupEvent);

        if ($request->getMethod() == 'POST' && !$importedGroupEvent) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GroupEvent $groupEvent */
                $groupEvent = $form->getData();
                $groupEvent->setUser($this->getUser());

                // Event is automatically approved if user is group organizer or super admin
                if ($groupEvent->getGroup()->getOwner() === $groupEvent->getUser() || $this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
                    $groupEvent->setApproved(true);
                }

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));

                $this->getGroupEventService()->createEvent($groupEvent);

                $tagManager->addTags($tags, $groupEvent);

                $tagManager->saveTagging($groupEvent);

                if ($groupEvent->isApproved()) {
                    $this->setFlash('success', 'Your event has been successfully added.');

                    if ($groupEvent->getExternalUrl()) {
                        return $this->redirect($this->generateUrl('group_show', array(
                            'slug' => $group->getSlug()
                        )) . '#events');
                    }

                    return $this->redirect($this->generateUrl('group_event_view', array(
                        'groupSlug' => $group->getSlug(),
                        'eventSlug' => $groupEvent->getSlug()
                    )));
                } else {
                    $this->setFlash('success', 'Success! Your event has been created. The group organizer has been notified via email to review your event. If approved, your event will be listed on the group page allowing other members to RSVP for your event.');

                    return $this->redirect($this->generateUrl('group_show', array(
                        'slug' => $group->getSlug()
                    )) . '#events');
                }

            }
        }

        return $this->render('EventBundle:GroupEvent:new.html.twig', array(
            'form' => $form->createView(),
            'group' => $group,
            'existingEvents' => $existingEvents,
            'importedGroupEvent' => $importedGroupEvent
        ));
    }

    /**
     * @Secure(roles="ROLE_USER")
     */
    public function newFromImportAction($groupSlug, $eventId, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        /** @var Group $group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$this->getGroupManager()->isAllowedTo($this->getUser(), $group, $this->getCurrentSite(), 'AddEvent')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        $importedGroupEvent = $this->getGroupEventService()->findOneBy(array('id' => $eventId));

        if (!$importedGroupEvent) {
            throw new NotFoundHttpException('Event to import from does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->cloneGroupEvent($importedGroupEvent);
        $groupEvent->setGroup($group);

        $form = $this->createForm('groupEvent', $groupEvent);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GroupEvent $groupEvent */
                $groupEvent = $form->getData();
                $groupEvent->setUser($this->getUser());

                // Event is automatically approved if user is group organizer or super admin
                if ($groupEvent->getGroup()->getOwner() === $groupEvent->getUser() || $this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
                    $groupEvent->setApproved(true);
                }

                $this->getGroupEventService()->createEvent($groupEvent);

                if ($groupEvent->isApproved()) {
                    $this->setFlash('success', 'Your event has been successfully added.');

                    if ($groupEvent->getExternalUrl()) {
                        return $this->redirect($this->generateUrl('group_show', array(
                            'slug' => $group->getSlug()
                        )) . '#events');
                    }

                    return $this->redirect($this->generateUrl('group_event_view', array(
                        'groupSlug' => $group->getSlug(),
                        'eventSlug' => $groupEvent->getSlug()
                    )));
                } else {
                    $this->setFlash('success', 'Success! Your event has been created. The group organizer has been notified via email to review your event. If approved, your event will be listed on the group page allowing other members to RSVP for your event.');

                    return $this->redirect($this->generateUrl('group_show', array(
                        'slug' => $group->getSlug()
                    )) . '#events');
                }

            }
        }

        return $this->render('EventBundle:GroupEvent:new.html.twig', array(
            'form' => $form->createView(),
            'group' => $group,
            'importedGroupEvent' => $importedGroupEvent
        ));
    }

    /**
     * Only event owner can edit their event
     */
    public function editAction($groupSlug, $eventId, Request $request)
    {
        $group      = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));
        $tagManager = $this->getTagManager();

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        // check for edit access
        if (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) && !$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new AccessDeniedException();
        }

        $tagManager->loadTagging($groupEvent);

        $form = $this->createForm('groupEvent', $groupEvent);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GroupEvent $groupEvent */
                $groupEvent = $form->getData();
                $groupEvent->setUser($this->getUser());

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));

                $this->getGroupEventService()->updateEvent($groupEvent);

                $tagManager->addTags($tags, $groupEvent);
                $tagManager->saveTagging($groupEvent);

                $this->setFlash('success', 'Event has been saved successfully.');

                return $this->redirect($this->generateUrl('group_event_view', array(
                    'groupSlug' => $group->getSlug(),
                    'eventSlug' => $groupEvent->getSlug()
                )));
            }
        }

        return $this->render('EventBundle:GroupEvent:edit.html.twig', array(
            'form' => $form->createView(),
            'group' => $group,
            'event' => $groupEvent
        ));
    }

    public function viewAction($groupSlug, $eventSlug)
    {
        /** @var $group Group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
            'published' => true,
            'deleted' => false,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if ($groupEvent->getType() == GroupEvent::TYPE_FORUM){
            return $this->redirect($this->generateUrl('idea_show_all', array('groupSlug' => $groupSlug, 'eventSlug' => $eventSlug)));
        }

        if (!$groupEvent->isApproved()) {
            $this->basicSecurityCheck(array('ROLE_USER'));
            if ($this->getUser() != $groupEvent->getUser() && !$this->getGroupManager()->isAllowedTo($this->getUser(), $group, $this->getCurrentSite(), 'ApproveEvent') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                throw new AccessDeniedHttpException('This event has not been approved by the group owner yet.');
            }
        }

        $attendance = $this->getCurrentUserApproved($groupEvent);
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

        return $this->render('EventBundle:GroupEvent:view.html.twig', array(
            'group'         => $group,
            'event'         => $groupEvent,
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_GROUP, 'id'=>$group->getId()),
            'attendance'    => $attendance,
            'isAdmin'       => $isAdmin,
        ));
    }

    public function contactAction($groupSlug, $eventSlug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
            'published' => true,
            'deleted' => false,
            'approved' => true,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        // check for edit access (permissions match those required to send email)
        if (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) && !$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new AccessDeniedException();
        }

        $hitEmailLimit = $this->getDoctrine()->getEntityManager()->getRepository('EventBundle:GroupEventEmail')->hasUserHitEmailLimitForEvent($this->getCurrentUser(), $groupEvent);

        if ($hitEmailLimit) {
            $this->setFlash('error', 'platformd.events.event_contact.limit_hit');

            if ($groupEvent->getExternalUrl()) {
                return $this->redirect($this->generateUrl('group_show', array(
                    'slug' => $group->getSlug()
                )) . '#events');
            }

            return $this->redirect($this->generateUrl('group_event_view', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $groupEvent->getSlug()
            )));
        }

        $email = new GroupEventEmail();

        $emailLocale = $group->getOwner()->getLocale() ?: 'en';
        $email->setSubject($this->trans(
            'platformd.event.email.attendees_contact.title',
            array('%eventName%' => $groupEvent->getName()),
            'messages',
            $emailLocale
        ));

        $form = $this->createFormBuilder($email)
            ->add('users', 'text', array(
                'property_path' => false,
                'label' => 'platformd.events.event_contact.form.recipients',
                'help' => 'platformd.events.event_contact.form.recipient_help',
            ))
            ->add('message', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor'),
                'label' => 'platformd.events.event_contact.form.message',
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $email = $form->getData();

                $recipientsString = $form->get('users')->getData();
                $recipients = array();

                if ($recipientsString === null) {

                    $email->setSentToAll(true);

                } else {

                    $recipientArr = explode(',', $recipientsString);
                    $userManager = $this->getUserManager();

                    foreach ($recipientArr as $recipient) {
                        $user = $userManager->findUserBy(array('username' => $recipient));
                        if ($user && $groupEvent->getAttendees()->contains($user)) {
                            $recipients[] = $user;
                        }
                    }
                }

                $recipientCount = count($recipients);

                if ($recipientCount < 1 && $recipientsString !== null) {

                    $this->setFlash('error', 'No valid recipients found.');

                    return $this->render('EventBundle:GroupEvent:contact.html.twig', array(
                        'group' => $group,
                        'event' => $groupEvent,
                        'form'  => $form->createView(),
                    ));
                }

                $email->setEvent($groupEvent);
                $email->setSender($this->getUser());
                $email->setSite($this->getCurrentSite());
                $email->setRecipients($recipients);

                $content = $email->getMessage();

                $email->setMessage($this->trans(
                    'platformd.event.email.attendees_contact.message',
                    array(
                        '%content%' => $content,
                        '%eventName%' => $groupEvent->getName(),
                        '%eventUrl%' => $this->generateUrl('group_event_view', array('groupSlug' => $groupSlug, 'eventSlug' => $eventSlug), true),
                        '%organizerName%' => $this->getUser()->getUsername(),
                    ),
                    'messages',
                    $emailLocale
                ));

                $emailManager = $this->container->get('platformd.model.email_manager');
                $queueResult  = $emailManager->queueMassEmail($email);

                $this->setFlash('success', $this->transChoice(
                    'platformd.events.event_contact.confirmation',
                    $recipientCount,
                    array('%attendeeCount%' => ($recipientCount > 0 ? $recipientCount : 'all')),
                    'messages',
                    $emailLocale
                ));

                if ($groupEvent->getExternalUrl()) {
                    return $this->redirect($this->generateUrl('group_show', array(
                        'slug' => $group->getSlug()
                    )) . '#events');
                }

                return $this->redirect($this->generateUrl('group_event_view', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $groupEvent->getSlug()
                )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('EventBundle:GroupEvent:contact.html.twig', array(
            'group' => $group,
            'event' => $groupEvent,
            'form'  => $form->createView(),
        ));
    }

    public function emailPreviewAction($groupSlug, $eventSlug, Request $request)
    {
        $email = new GroupEventEmail();

        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        $event = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
        ));

        $emailLocale = $group->getOwner()->getLocale() ?: 'en';
        $email->setSubject($this->trans(
            'platformd.event.email.attendees_contact.title',
            array('%eventName%' => $event->getName()),
            'messages',
            $emailLocale
        ));

        $form = $this->createFormBuilder($email)
            ->add('users', 'text', array(
                'property_path' => false,
                'label' => 'platformd.events.event_contact.form.recipients',
                'help' => 'platformd.events.event_contact.form.recipient_help',
            ))
            ->add('message', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor'),
                'label' => 'platformd.events.event_contact.form.message',
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $email = $form->getData();

                $content = $email->getMessage();

                $email->setMessage($this->trans(
                    'platformd.event.email.attendees_contact.message',
                    array(
                        '%content%' => $content,
                        '%eventName%' => $event->getName(),
                        '%eventUrl%' => $this->generateUrl('group_event_view', array('groupSlug' => $groupSlug, 'eventSlug' => $eventSlug), true),
                        '%organizerName%' => $this->getUser()->getUsername(),
                    ),
                    'messages',
                    $emailLocale
                ));

                return $this->render('EventBundle::contactPreview.html.twig', array(
                    'subject' => $email->getSubject(),
                    'message' => $email->getMessage(),
                ));
            }
        }

        $this->setFlash('error', 'platformd.events.event_contact.error');
        return $this->redirect($this->generateUrl('group_event_contact', array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
        )));
    }

    /**
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function attendeesAction($groupSlug, $eventSlug)
    {
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        $attendees = $this->getGroupEventService()->getAttendeeList($groupEvent);

        return $this->render('EventBundle:GroupEvent:attendees.html.twig', array(
            'event' => $groupEvent,
            'attendees' => $attendees,
        ));
    }

    public function removeAttendeeAction($groupSlug, $eventSlug, $userId)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) && !$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new AccessDeniedException();
        }

        $user = $this->getUserManager()->findUserBy(array('id' => $userId));

        if (!$user) {
            throw $this->createNotFoundException(sprintf('No user for id "%s"', $userId));
        }

        $this->getGroupEventService()->unregister($groupEvent, $user);

        $locale = $user->getLocale() ?: 'en';

        $subject = $this->trans('platformd.event.email.attendee_removed.title', array(
            '%eventName%' => $groupEvent->getName(),
        ), 'messages', $locale);

        $url        = $this->generateUrl('group_event_view', array('groupSlug' => $groupSlug, 'eventSlug' => $groupEvent->getSlug()), true);

        $message = nl2br($this->trans('platformd.event.email.attendee_removed.message', array(
            '%username%'       => $user->getUsername(),
            '%url%'            => $url,
            '%eventName%'      => $groupEvent->getName(),
        ), 'messages', $locale));

        $emailType  = "Event unregister notification";
        $emailTo    = $user->getEmail();
        $this->get('platformd.model.email_manager')->sendHtmlEmail($emailTo, $subject, $message, $emailType);

        $this->setFlash('success', sprintf('%s has been successfully removed from this event!', $user->getUsername()));

        $attendees = $this->getGroupEventService()->getAttendeeList($groupEvent);
        return $this->redirect($this->generateUrl('group_event_attendees', array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $groupEvent->getSlug(),
        )));
    }

    /**
     * Lists all events pending approval
     * Only for group owner
     *
     * @param $groupSlug
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function pendingApprovalListAction($groupSlug)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        /** @var $group Group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$this->getGroupManager()->isAllowedTo($this->getUser(), $group, $this->getCurrentSite(), 'ApproveEvent')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        $pendingApprovals = $this->getGroupEventService()->getPendingApprovalEventsForGroup($group);
        $groupPermissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('EventBundle:GroupEvent:pending.html.twig', array(
            'pendingApprovals' => $pendingApprovals,
            'group' => $group,
            'permissions' => $groupPermissions,
        ));
    }

    /**
     * Approves a Group Event
     */
    public function approveAction($groupSlug, $eventId)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        /** @var $group Group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$this->getGroupManager()->isAllowedTo($this->getUser(), $group, $this->getCurrentSite(), 'ApproveEvent')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        $this->getGroupEventService()->approveEvent($groupEvent);

        $this->setFlash('success', 'An automated email was sent to the event organizer and the group members to inform them about the event.');

        return $this->redirect($this->generateUrl('group_event_pending_approval', array(
            'groupSlug' => $group->getSlug()
        )));
    }

    /**
     * Sets an event as canceled
     * Triggers email notifications
     *
     * @param $eventId
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function cancelAction($eventId)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if (
            !$this->getGroupManager()->isAllowedTo($this->getUser(), $groupEvent->getGroup(), $this->getCurrentSite(), 'CancelEvent') &&
            (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) && !$this->isGranted('ROLE_SUPER_ADMIN'))
        ) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        if (!$groupEvent->getActive()) {
            $this->setFlash('error', 'Event is already canceled!');
        } else {
            $this->getGroupEventService()->cancelEvent($groupEvent);

            $this->setFlash('success', ' Your event is cancelled. Notify your attendees via email below.');
        }

        return $this->redirect($this->generateUrl('group_event_contact', array(
            'groupSlug' => $groupEvent->getGroup()->getSlug(),
            'eventSlug' => $groupEvent->getSlug()
        )));
    }

    public function activateAction($eventId)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if (
            !$this->getGroupManager()->isAllowedTo($this->getUser(), $groupEvent->getGroup(), $this->getCurrentSite(), 'CancelEvent') &&
            (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) && !$this->isGranted('ROLE_SUPER_ADMIN'))
        ) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        if ($groupEvent->getActive()) {
            $this->setFlash('error', 'Event is already active!');
        } else {
            $this->getGroupEventService()->activateEvent($groupEvent);

            $this->setFlash('success', ' Your event is active. Notify your attendees via email below.');
        }

        return $this->redirect($this->generateUrl('group_event_contact', array(
            'groupSlug' => $groupEvent->getGroup()->getSlug(),
            'eventSlug' => $groupEvent->getSlug()
        )));
    }

    public function rsvpAjaxAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['id']) || !isset($params['rsvp'])) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $id     = (int) $params['id'];
        $rsvp   = $params['rsvp'];

        if (!$this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => 'You must be logged in to RSVP to an event')));
            return $response;
        }

        $groupEvent = $this->getGroupEventService()->find($id);
        $user       = $this->getUser();

        if (!$groupEvent) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Event not found!")));
            return $response;
        }

        $group = $groupEvent->getGroup();

        if (!$this->getGroupManager()->isAllowedTo($user, $group, $this->getCurrentSite(), 'JoinEvent')) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "You are not allowed to rsvp to this event!")));
            return $response;
        }

        $isAttending = $this->getGroupEventService()->isUserAttending($groupEvent, $user);

        if ($rsvp == 0 && $isAttending) {
            $this->getGroupEventService()->unregister($groupEvent, $user);
        } elseif ($rsvp > 0 && !$isAttending) {
            $this->getGroupEventService()->register($groupEvent, $user);
        }

        $attendeeCount = $groupEvent->getAttendeeCount();

        $response->setContent(json_encode(array("success" => true, "attendeeCount" => $attendeeCount)));
        return $response;
    }

    public function registerAction($groupSlug, $eventId, Request $request)
    {
        $this->basicSecurityCheck('ROLE_USER');

        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));
        $user = $this->getUser();

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->find($eventId);

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if (!$this->getGroupManager()->isAllowedTo($user, $group, $this->getCurrentSite(), 'JoinEvent')) {
            $this->setFlash('error', $this->trans('platformd.events.event_show.not_allowed_register'));
            return $this->redirect($this->generateUrl('group_event_view', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $groupEvent->getSlug(),
            )));
        }

        $this->getGroupEventService()->register($groupEvent, $user);
        $this->getGroupManager()->autoJoinGroup($group, $user);

        if ($groupEvent->getPrivate()){
            $this->setFlash('success', "We have received your request for API access. You will receive a response by email within 1 working day.");

        } else {
            $this->setFlash('success', $this->trans(
                'platformd.events.event_show.group_joined',
                array('%groupName%' => $group->getName()))
            );
        }

        return $this->redirect($this->generateUrl('group_event_view', array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $groupEvent->getSlug(),
        )));
    }

    public function disableAjaxAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['id'])) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $id = (int) $params['id'];

        if (!$this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => 'You must be logged in to delete an event')));
            return $response;
        }

        $groupEvent = $this->getGroupEventService()->find($id);
        $user           = $this->getUser();

        if (!$groupEvent) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Event not found!")));
            return $response;
        }

        if (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) && $user->getAdminLevel() === null) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "You are not authorized to delete this event.")));
            return $response;
        }

        $groupEvent->setPublished(false);

        $this->getGroupEventService()->updateEvent($groupEvent);

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    /**
     * Sets an event as deleted
     *
     * @param $groupSlug, $eventId
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function deleteAction($groupSlug, $eventId)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if (
            !$this->getGroupManager()->isAllowedTo($this->getUser(), $groupEvent->getGroup(), $this->getCurrentSite(), 'DeleteEvent') &&
            (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) && !$this->isGranted('ROLE_SUPER_ADMIN'))
        ) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        if ($groupEvent->getDeleted()) {
            $this->setFlash('error', 'Event is already deleted!');
        } else {
            $this->getGroupEventService()->deleteEvent($groupEvent);

            $this->setFlash('success', ' Your event is deleted.');
        }

        return $this->redirect($this->generateUrl('group_show', array('slug' => $groupEvent->getGroup()->getSlug())) . '#events');
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }


    public function getCurrentUserApproved($event)
    {
        $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');
        $user = $this->getCurrentUser();
        $attendance = $rsvpRepo->getUserApprovedStatus($event, $user);

        return $attendance;
    }


}
