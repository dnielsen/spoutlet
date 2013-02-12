<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\SpoutletBundle\Entity\Group,
    Platformd\SpoutletBundle\Model\GroupManager
;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\EventBundle\Form\Type\EventType,
    Platformd\EventBundle\Service\EventService,
    Platformd\EventBundle\Entity\GroupEventTranslation,
    Platformd\EventBundle\Entity\GroupEventEmail
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
    /**
     * @Secure(roles="ROLE_USER")
     */
    public function newAction($groupSlug, Request $request)
    {
        /** @var Group $group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$group->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'AddEvent')) {
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
            $site = $this->getSiteFromLocale($locale);
            $groupEvent->addTranslation(new GroupEventTranslation($site, $groupEvent));
        }

        // Event is automatically approved if user is group organizer or super admin
        if ($groupEvent->getGroup()->getOwner() === $groupEvent->getUser() || $this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
            $groupEvent->setApproved(true);
        }

        $form = $this->createForm('groupEvent', $groupEvent);

        if ($request->getMethod() == 'POST' && !$importedGroupEvent) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GroupEvent $groupEvent */
                $groupEvent = $form->getData();
                $groupEvent->setUser($this->getUser());

                $this->getGroupEventService()->createEvent($groupEvent);

                if ($groupEvent->isApproved()) {
                    $this->setFlash('success', 'New event posted successfully');

                    return $this->redirect($this->generateUrl('group_event_view', array(
                        'groupSlug' => $group->getSlug(),
                        'eventSlug' => $groupEvent->getSlug()
                    )));
                } else {
                    $this->setFlash('success', 'New event posted successfully and is ending approval from Group Organizer');

                    return $this->redirect($this->generateUrl('group_show', array(
                        'slug' => $group->getSlug()
                    )) . '#events');
                }

            } else {
                $this->setFlash('error', 'Something went wrong');
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
        /** @var Group $group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$group->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'AddEvent')) {
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

                $this->getGroupEventService()->createEvent($groupEvent);

                if ($groupEvent->isApproved()) {
                    $this->setFlash('success', 'New event posted successfully');

                    return $this->redirect($this->generateUrl('group_event_view', array(
                        'groupSlug' => $group->getSlug(),
                        'eventSlug' => $groupEvent->getSlug()
                    )));
                } else {
                    $this->setFlash('success', 'New event posted successfully and is ending approval from Group Organizer');

                    return $this->redirect($this->generateUrl('group_show', array(
                        'slug' => $group->getSlug()
                    )) . '#events');
                }

            } else {
                $this->setFlash('error', 'Something went wrong');
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
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

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
        if (false === $this->getSecurity()->isGranted('EDIT', $groupEvent))
        {
            throw new AccessDeniedException();
        }

        $form = $this->createForm('groupEvent', $groupEvent);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GroupEvent $groupEvent */
                $groupEvent = $form->getData();
                $groupEvent->setUser($this->getUser());

                $this->getGroupEventService()->updateEvent($groupEvent);
                $this->setFlash('success', 'New event posted successfully');

                return $this->redirect($this->generateUrl('group_event_edit', array(
                    'groupSlug' => $group->getSlug(),
                    'eventId' => $groupEvent->getId()
                )));
            } else {
                $this->setFlash('error', 'Something went wrong');
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

        if (!$groupEvent->isApproved()) {
            if (!$group->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'ApproveEvent') && !$this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
                throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
            }
        } elseif (!$group->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'ViewEvent')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        $isAttending = $this->getGroupEventService()->isUserAttending($groupEvent, $this->getUser());

        $attendeeCount = $this->getGroupEventService()->getAttendeeCount($groupEvent);

        return $this->render('EventBundle:GroupEvent:view.html.twig', array(
            'event'         => $groupEvent,
            'attendeeCount' => $attendeeCount,
            'isAttending'   => $isAttending,
        ));
    }

    public function contactAction($groupSlug, $eventSlug, Request $request)
    {
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
        if (false === $this->getSecurity()->isGranted('EDIT', $groupEvent))
        {
            throw new AccessDeniedException();
        }

        $email = new GroupEventEmail();

        $form = $this->createFormBuilder($email)
            ->add('subject', 'text')
            ->add('recipients', 'text', array(
                'read_only' => true,
                'help' => 'Leave blank to send to all attendees or click on users to the right to choose specific recipients.',
            ))
            ->add('message', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor')
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $email = $form->getData();
                $email->setGroupEvent($groupEvent);

                $recipients = array();

                if ($email->getRecipients() === null) {

                    foreach ($groupEvent->getAttendees() as $recipient) {
                        $recipients[] = $recipient;
                    }

                } else {

                    $recipientArr = explode(',', $email->getRecipients());
                    $userManager = $this->getUserManager();

                    foreach ($recipientArr as $recipient) {
                        $user = $userManager->loadUserByUsername($recipient);
                        if ($user && $groupEvent->getAttendees()->contains($user)) {
                            $recipients[] = $user;
                        }
                    }
                }

                $email->setRecipients($recipients);

                if (count($email->getRecipients()) > 0) {
                    $sendCount = $this->getGroupEventService()->sendEmail($email);
                } else {
                    $this->setFlash('error', 'No valid recipients found.');
                    return $this->redirect($this->generateUrl('group_event_contact', array(
                        'groupSlug' => $groupSlug,
                        'eventSlug' => $groupEvent->getSlug(),
                        'form'  => $form->createView(),
                    )));
                }

                $this->setFlash('success', sprintf('Email sent to %d attendees.', $sendCount));
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

        $form = $this->createFormBuilder($email)
            ->add('subject', 'text')
            ->add('recipients', 'text', array(
                'read_only' => true,
                'help' => 'Leave blank to send to all attendees or click on users to the right to choose specific recipients.',
            ))
            ->add('message', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor')
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $email = $form->getData();

                return $this->render('EventBundle::contactPreview.html.twig', array(
                    'subject' => $email->getSubject(),
                    'message' => $email->getMessage(),
                ));
            }
        }

        $this->setFlash('error', 'There was an error previewing your email!');
        return $this->redirect($this->generateUrl('group_event_contact', array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $groupEvent->getSlug(),
            'form'  => $form->createView(),
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
        /** @var $group Group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$group->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'ApproveEvent')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        $pendingApprovals = $this->getGroupEventService()->getPendingApprovalEventsForGroup($group);

        return $this->render('EventBundle:GroupEvent:pending.html.twig', array(
            'pendingApprovals' => $pendingApprovals,
            'group' => $group
        ));
    }

    /**
     * Approves a Group Event
     */
    public function approveAction($groupSlug, $eventId)
    {
        /** @var $group Group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$group->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'ApproveEvent')) {
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

        $this->setFlash('success', 'Event has been approved');

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
        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if (
            !$groupEvent->getGroup()->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'CancelEvent') &&
            false === $this->getSecurity()->isGranted('EDIT', $groupEvent)
        ) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        if (!$groupEvent->getActive()) {
            $this->setFlash('error', 'Event is already canceled!');
        } else {
            $this->getGroupEventService()->cancelEvent($groupEvent);

            $this->setFlash('success', 'Event has been canceled successfully and attendees will be notified!');
        }

        return $this->redirect($this->generateUrl('group_event_edit', array(
            'groupSlug' => $groupEvent->getGroup()->getSlug(),
            'eventId' => $groupEvent->getId()
        )));
    }

    public function activateAction($eventId)
    {
        /** @var $groupEvent GroupEvent */
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        if (
            !$groupEvent->getGroup()->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'CancelEvent') &&
            false === $this->getSecurity()->isGranted('EDIT', $groupEvent)
        ) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        if ($groupEvent->getActive()) {
            $this->setFlash('error', 'Event is already active!');
        } else {
            $this->getGroupEventService()->activateEvent($groupEvent);

            $this->setFlash('success', 'Event has been activated successfully and attendees will be notified!');
        }

        return $this->redirect($this->generateUrl('group_event_edit', array(
            'groupSlug' => $groupEvent->getGroup()->getSlug(),
            'eventId' => $groupEvent->getId()
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

        $isAttending = $this->getGroupEventService()->isUserAttending($groupEvent, $user);

        if ($rsvp == 0 && $isAttending) {
            $this->getGroupEventService()->unregister($groupEvent, $user);
        } elseif ($rsvp > 0 && !$isAttending) {
            $this->getGroupEventService()->register($groupEvent, $user);
        }

        $attendeeCount = $this->getGroupEventService()->getAttendeeCount($groupEvent);

        $response->setContent(json_encode(array("success" => true, "attendeeCount" => $attendeeCount)));
        return $response;
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

        if (false === $this->getSecurity()->isGranted('EDIT', $groupEvent) || $user->getAdminLevel() === null) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "You are not authorized to delete this event.")));
            return $response;
        }

        $groupEvent->setPublished(false);

        $this->getGroupEventService()->updateEvent($groupEvent);

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    /**
     * @return GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

    /**
     * @return \Platformd\EventBundle\Service\GroupEventService
     */
    private function getGroupEventService()
    {
        return $this->get('platformd_event.service.group_event');
    }
}
