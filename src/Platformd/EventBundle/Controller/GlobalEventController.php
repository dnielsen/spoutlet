<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\EventBundle\Service\GlobalEventService,
    Platformd\EventBundle\Entity\GlobalEvent,
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

        return $this->render('EventBundle:GlobalEvent:view.html.twig', array('event' => $event));
    }

    /**
     * @return GlobalEventService
     */
    private function getGlobalEventService()
    {
        return $this->get('platformd_event.service.global_event');
    }
}
