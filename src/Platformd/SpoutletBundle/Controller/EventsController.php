<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event,
	Platformd\SpoutletBundle\Entity\EventRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EventsController extends Controller
{
	public function indexAction()
	{
		$current_events = $this->getEventsRepo()->getCurrentEvents(5);
		$past_events    = $this->getEventsRepo()->getPastEvents(5);

		return $this->render('SpoutletBundle:Events:index.html.twig',
			array(
				'current_events' => $current_events,
				'past_events'    => $past_events,
			));
	}

	public function currentAction()
	{
		return $this->render('SpoutletBundle:Events:current.html.twig', array(
			'events' => $this->getEventsRepo()->getCurrentEvents(),
		));
	}

	public function pastAction()
	{
		return $this->render('SpoutletBundle:Events:past.html.twig', array(
			'events' => $this->getEventsRepo()->getPastEvents(),
		));
	}

    /**
     * The event show page
     *
     * @param $id
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     * @throws \Symfony\Bundle\FrameworkBundle\Controller\NotFoundHttpException
     */
	public function eventAction($id)
	{
		$event = $this->getEventsRepo()->find($id);

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for id "%s"', $id));
        }

		return $this->render('SpoutletBundle:Events:event.html.twig', array('event' => $event));
	}

	private function getCurrentEvents()
	{
		return $this->getEventsRepo()->getCurrentEvents();
	}

	private function getUpcomingEvents()
	{
		return $this->getEventsRepo()->getUpcomingEvents();
	}
	
	private function getEventsRepo()
	{
		$em = $this->getDoctrine()->getEntityManager();
		return $em->getRepository('SpoutletBundle:Event');
	}
}