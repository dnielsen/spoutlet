<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\EventBundle\Service\GlobalEventService,
    Platformd\EventBundle\Entity\GlobalEvent,
    Platformd\EventBundle\Entity\GlobalEventEmail,
    Platformd\EventBundle\Form\Type\GlobalEventType,
    Platformd\EventBundle\Entity\GlobalEventTranslation
;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\Security\Core\Exception\AccessDeniedException
;

class GlobalEventController extends Controller
{
    private $globalEventService;

    public function _userEventListAction()
    {
        if (!$this->isGranted('ROLE_USER')) {
            $response = new Response();
            $this->varnishCache($response, 1);

            return $response;
        }

        $user = $this->getUser();

        $globalEventResult = $this->getGlobalEventService()->getAllEventsUserIsAttending($user);
        $groupEventResult  = $this->getGroupEventService()->getAllEventsUserIsAttending($user);

        $globalEvents = $groupEvents = array();

        foreach ($globalEventResult as $event) {
            $globalEvents[] = $event['id'];
        }

        foreach ($groupEventResult as $event) {
            $groupEvents[] = $event['id'];
        }

        $groupEvents  = implode(',', $groupEvents);
        $globalEvents = implode(',', $globalEvents);

        $response = $this->render('EventBundle:GlobalEvent:_userEventList.html.twig', array(
            'globalEvents' => $globalEvents,
            'groupEvents'  => $groupEvents,
        ));

        $this->varnishCache($response, 1);

        return $response;
    }

    public function _globalEventUserInfoAction($id)
    {
        $event = $this->getGlobalEventService()->find($id);

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for id "%s"', $id));
        }

        $user = $this->getUser();

        $isAttending = $this->isGranted('ROLE_USER') ? $this->getGlobalEventService()->isUserAttending($event, $user) : false;
        $isOwner     = $user == $event->getUser();

        $attendeeCount = $event->getAttendeeCount();

        $response = $this->render('EventBundle:GlobalEvent:_eventUserInfo.html.twig', array(
            'isAttending'   => $isAttending,
            'isOwner'       => $isOwner,
            'attendeeCount' => $attendeeCount,
        ));

        $this->varnishCache($response, 1);

        return $response;
    }

    /**
     * Lists all events, upcoming and past
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $site = $this->getCurrentSite();
        $hasGroups = $site->getSiteFeatures()->getHasGroups();

        $upcomingGlobalEvents = $this->getGlobalEventService()->findUpcomingEventsForSite($site, 0);
        $pastGlobalEvents     = $this->getGlobalEventService()->findPastEventsForSite($site, 0);
        $upcomingGroupEvents  = $hasGroups ? $this->getGroupEventService()->findUpcomingEventsForSite($site, 0) : array();
        $pastGroupEvents      = $hasGroups ? $this->getGroupEventService()->findPastEventsForSite($site, 0) : array();

        $upcomingEvents       = array_merge($upcomingGlobalEvents, $upcomingGroupEvents);
        $pastEvents           = array_merge($pastGroupEvents, $pastGlobalEvents);

        uasort($upcomingEvents, array($this, 'eventCompare'));
        uasort($pastEvents, array($this, 'eventCompare'));

        return $this->render('EventBundle:GlobalEvent:list.html.twig', array(
            'upcomingEvents' => $upcomingEvents,
            'pastEvents'     => $pastEvents,
        ));
    }

    /**
     * Unique event view page
     *
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function viewAction($slug)
    {
        /*
         * Notice that this does *not* respect "published". This is on purpose,
         * because the client wants to be able to preview events to the client
         */
        $event = $this->getGlobalEventService()->findOneBySlugForSite($slug,$this->getCurrentSite());

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
        }

        // if we have an external url, then we should never get to this page
        if ($event->getExternalUrl()) {
            return new RedirectResponse($event->getExternalUrl());
        }

        $isAttending = $this->isGranted('ROLE_USER') ? $this->getGlobalEventService()->isUserAttending($event, $this->getUser()) : false;

        return $this->render('EventBundle:GlobalEvent:view.html.twig', array(
            'event' => $event,
            'isAttending'   => $isAttending,
        ));
    }

    /**
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function attendeesAction($slug)
    {
        $event = $this->getGlobalEventService()->findOneBySlugForSite($slug,$this->getCurrentSite());

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
        }

        $attendees = $this->getGlobalEventService()->getAttendeeList($event);

        return $this->render('EventBundle:GlobalEvent:attendees.html.twig', array(
            'event' => $event,
            'attendees' => $attendees,
        ));
    }

    public function removeAttendeeAction($slug, $userId)
    {
        $event = $this->getGlobalEventService()->findOneBySlugForSite($slug,$this->getCurrentSite());

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
        }

        if (false === $this->getSecurity()->isGranted('EDIT', $event) && $this->getUser()->getAdminLevel() === null) {
            throw new AccessDeniedException();
        }

        $user = $this->getUserManager()->findUserBy(array('id' => $userId));

        if (!$user) {
            throw $this->createNotFoundException(sprintf('No user for id "%s"', $userId));
        }

        $this->getGlobalEventService()->unregister($event, $user);

        $locale = $user->getLocale() ?: 'en';

        $subject = $this->trans('platformd.event.email.attendee_removed.title', array(
            '%eventName%' => $event->getName(),
        ), 'messages', $locale);

        $url        = $this->generateUrl('global_event_view', array('slug' => $event->getSlug()), true);

        $message = nl2br($this->trans('platformd.event.email.attendee_removed.message', array(
            '%username%'       => $user->getUsername(),
            '%url%'            => $url,
            '%eventName%'      => $event->getName(),
        ), 'messages', $locale));

        $emailType  = "Event unregister notification";
        $emailTo    = $user->getEmail();
        $this->get('platformd.model.email_manager')->sendHtmlEmail($emailTo, $subject, $message, $emailType);

        $this->setFlash('success', sprintf('%s has been successfully removed from this event!', $user->getUsername()));

        $attendees = $this->getGlobalEventService()->getAttendeeList($event);

        return $this->redirect($this->generateUrl('global_event_attendees', array(
            'slug' => $event->getSlug(),
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

        $event = $this->getGlobalEventService()->find($id);

        $user       = $this->getUser();

        if (!$event) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Event not found!")));
            return $response;
        }

        $isAttending = $this->getGlobalEventService()->isUserAttending($event, $user);

        if ($rsvp == 0 && $isAttending) {
            $this->getGlobalEventService()->unregister($event, $user);
        } elseif ($rsvp > 0 && !$isAttending) {
            $this->getGlobalEventService()->register($event, $user);
        }

        $attendeeCount = $event->getAttendeeCount();

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

        $event = $this->getGlobalEventService()->find($id);
        $user  = $this->getUser();

        if (!$event) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Event not found!")));
            return $response;
        }

        if (false === $this->getSecurity()->isGranted('EDIT', $event) || $user->getAdminLevel() === null) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "You are not authorized to delete this event.")));
            return $response;
        }

        $event->setPublished(false);

        $this->getGlobalEventService()->updateEvent($event);

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    public function contactAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $event = $this->getGlobalEventService()->findOneBy(array(
            'slug' => $slug
        ));

        if (!$event) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        // check for edit access (permissions match those required to send email)
        if (false === $this->getSecurity()->isGranted('EDIT', $event) && $this->getUser()->getAdminLevel() === null)
        {
            throw new AccessDeniedException();
        }

        $email = new GlobalEventEmail();

        $emailLocale = $this->getLocale() ?: 'en';
        $email->setSubject($this->trans(
            'platformd.event.email.global_event_contact.title',
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

                $recipientsString = $form->get('users')->getData();
                $recipients = array();

                if ($recipientsString === null) {

                    foreach ($event->getAttendees() as $recipient) {
                        $recipients[] = $recipient;
                    }

                } else {

                    $recipientArr = explode(',', $recipientsString);
                    $userManager = $this->getUserManager();

                    foreach ($recipientArr as $recipient) {
                        $user = $userManager->findUserBy(array('username' => $recipient));
                        if ($user && $event->getAttendees()->contains($user)) {
                            $recipients[] = $user;
                        }
                    }
                }

                if (count($recipients) < 1) {

                    $this->setFlash('error', 'No valid recipients found.');

                    return $this->render('EventBundle:GlobalEvent:contact.html.twig', array(
                        'event' => $event,
                        'form'  => $form->createView(),
                    ));
                }

                $email = $form->getData();
                $email->setEvent($event);
                $email->setSender($this->getUser());
                $email->setSite($this->getCurrentSite());
                $email->setRecipients($recipients);

                $content = $email->getMessage();

                $email->setMessage(str_replace('%content%', '------'.$content.'------', nl2br($this->trans(
                    'platformd.event.email.global_event_contact.message',
                    array(
                        '%eventName%' => $event->getName(),
                        '%organizerName%' => $this->getUser()->getUsername(),
                    ),
                    'messages',
                    $emailLocale
                ))));

                $sendCount = $this->getGlobalEventService()->sendEmail($email);

                $this->setFlash('success', $this->trans(
                    'platformd.events.event_contact.confirmation',
                    array('%attendeeCount%' => $sendCount),
                    'messages',
                    $emailLocale
                ));

                if ($event->getExternalUrl()) {
                    return $this->redirect($this->generateUrl('global_events_index'));
                }

                return $this->redirect($this->generateUrl('global_event_view', array(
                    'slug' => $slug
                )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('EventBundle:GlobalEvent:contact.html.twig', array(
            'event' => $event,
            'form'  => $form->createView(),
        ));
    }

    public function emailPreviewAction($slug, Request $request)
    {
        $email = new GlobalEventEmail();
        $event = $this->getGlobalEventService()->findOneBy(array('slug' => $slug));

        $emailLocale = $this->getLocale() ?: 'en';
        $email->setSubject($this->trans(
            'platformd.event.email.global_event_contact.title',
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

                $email->setMessage(str_replace('%content%', '------'.$content.'------', nl2br($this->trans(
                    'platformd.event.email.global_event_contact.message',
                    array(
                        '%eventName%' => $event->getName(),
                        '%organizerName%' => $this->getUser()->getUsername(),
                    ),
                    'messages',
                    $emailLocale
                ))));

                return $this->render('EventBundle::contactPreview.html.twig', array(
                    'subject' => $email->getSubject(),
                    'message' => $email->getMessage(),
                ));
            }
        }

        $this->setFlash('error', 'platformd.events.event_contact.error');
        return $this->redirect($this->generateUrl('global_event_contact', array(
            'slug' => $slug,
            'form'  => $form->createView(),
        )));
    }

    public function registerAction($id, Request $request)
    {
        $this->basicSecurityCheck('ROLE_USER');
        $user = $this->getUser();

        $event = $this->getGlobalEventService()->find($id);

        if (!$event) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        $this->getGlobalEventService()->register($event, $user);

        $this->setFlash('success', $this->trans('platformd.events.event_show.now_attending'));
        return $this->redirect($this->generateUrl('global_event_view', array(
            'slug' => $event->getSlug(),
        )));
    }

    private function eventCompare($a, $b) {

        if ($a->getStartsAt() == $b->getStartsAt()) {
            return 0;
        }
        return ($a->getStartsAt() < $b->getStartsAt()) ? 1 : -1;

    }

    private function getGamePageManager()
    {
        return $this->get('platformd.model.game_page_manager');
    }
}
