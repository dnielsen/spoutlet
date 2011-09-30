<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event,
	Platformd\SpoutletBundle\Entity\EventRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EventsController extends Controller
{
	public function indexAction() 
	{
		$current_events = $this->getCurrentEvents();
		$past_events = $this->getPastEvents();

		return $this->render('SpoutletBundle:Events:index.html.twig',
			array(
				'current_events' 	=> $current_events,
				'past_events' 		=> $past_events,
			));
	}

	public function eventAction($id) {
		$event = $this->getEventsRepo()->findOneById($id);
		return $this->render('SpoutletBundle:Events:event.html.twig', array('event' => $event));
	}
	
	private function getPastEvents()
	{
		$repo = $this->getEventsRepo();
		$query = $repo->createQueryBuilder('e')
			->where('e.starts_at < :cut_off')
			->setParameter('cut_off', new \DateTime('now'))
			->orderBy('e.starts_at', 'ASC')
			->getQuery();
		
		return $query->getResult();
	}

	private function getCurrentEvents() 
	{

		$repo = $this->getEventsRepo();
		$query = $repo->createQueryBuilder('e')
			->where('e.starts_at > :cut_off')
			->setParameter('cut_off', new \DateTime('now'))
			->orderBy('e.starts_at', 'ASC')
			->getQuery();
		
		return $query->getResult();
	}

	private function getEventsRepo() {
		$em = $this->getDoctrine()->getEntityManager();
		return $em->getRepository('SpoutletBundle:Event');
	}
}