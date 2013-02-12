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
    Symfony\Component\HttpFoundation\RedirectResponse
;

class GlobalEventController extends Controller
{
    /**
     * Lists all events, upcoming and past
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $upcomingEvents = $this->getGlobalEventService()->findUpcomingEventsForSite($this->getCurrentSite());
        $pastEvents = $this->getGlobalEventService()->findPastEventsForSite($this->getCurrentSite());

        return $this->render('EventBundle:GlobalEvent:list.html.twig', array(
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents
        ));
    }

    /**
     * Lists upcoming events and paginates
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function currentAction(Request $request)
    {
        $page = $request->query->get('page', 1);

        $events = $this->getGlobalEventService()->findUpcomingEventsForSite($this->getCurrentSite(), 20, $page, $pager);

        return $this->render('EventBundle:GlobalEvent:currentList.html.twig', array(
            'events' => $events,
            'pager' => $pager
        ));
    }

    /**
     * List past events and paginates
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pastAction(Request $request)
    {
        $page = $request->query->get('page', 1);

        $events = $this->getGlobalEventService()->findPastEventsForSite($this->getCurrentSite(), 20, $page, $pager);

        return $this->render('EventBundle:GlobalEvent:pastList.html.twig', array(
            'events' => $events,
            'pager' => $pager
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

        $isAttending = $this->getGlobalEventService()->isUserAttending($event, $this->getUser());

        $attendeeCount = $this->getGlobalEventService()->getAttendeeCount($event);

        return $this->render('EventBundle:GlobalEvent:view.html.twig', array(
            'event' => $event,
            'attendeeCount' => $attendeeCount,
            'isAttending'   => $isAttending,
        ));
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

        $attendeeCount = $this->getGlobalEventService()->getAttendeeCount($event);

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
        $event = $this->getGlobalEventService()->findOneBy(array(
            'slug' => $slug
        ));

        if (!$event) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        // check for edit access (permissions match those required to send email)
        if (false === $this->getSecurity()->isGranted('EDIT', $event))
        {
            throw new AccessDeniedException();
        }

        $email = new GlobalEventEmail();

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
                $email->setEvent($event);

                $recipients = array();

                if ($email->getRecipients() === null) {

                    foreach ($event->getAttendees() as $recipient) {
                        $recipients[] = $recipient;
                    }

                } else {

                    $recipientArr = explode(',', $email->getRecipients());
                    $userManager = $this->getUserManager();

                    foreach ($recipientArr as $recipient) {
                        $user = $userManager->loadUserByUsername($recipient);
                        if ($user && $event->getAttendees()->contains($user)) {
                            $recipients[] = $user;
                        }
                    }
                }

                $email->setRecipients($recipients);

                if (count($email->getRecipients()) > 0) {
                    $sendCount = $this->getGlobalEventService()->sendEmail($email);
                } else {
                    $this->setFlash('error', 'No valid recipients found.');
                    return $this->redirect($this->generateUrl('global_event_contact', array(
                        'slug' => $slug,
                        'form'  => $form->createView(),
                    )));
                }

                $this->setFlash('success', sprintf('Email sent to %d attendees.', $sendCount));
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
            'slug' => $slug,
            'form'  => $form->createView(),
        )));
    }

    /**
     * @return GlobalEventService
     */
    private function getGlobalEventService()
    {
        return $this->get('platformd_event.service.global_event');
    }
}
