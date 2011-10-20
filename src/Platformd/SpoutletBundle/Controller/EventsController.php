<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event,
	Platformd\SpoutletBundle\Entity\EventRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EventsController extends Controller
{
	private function getLocale()
	{
		return $this->get('session')->getLocale();
	}

	public function indexAction()
	{
		$current_events = $this->getEventsRepo()->getCurrentEvents($this->getLocale(), 5);
		$past_events    = $this->getEventsRepo()->getPastEvents($this->getLocale(), 5);

		return $this->render('SpoutletBundle:Events:index.html.twig',
			array(
				'current_events' => $current_events,
				'past_events'    => $past_events,
			));
	}

	public function currentAction()
	{
		return $this->render('SpoutletBundle:Events:current.html.twig', array(
			'events' => $this->getEventsRepo()->getCurrentEvents($this->getLocale()),
		));
	}

	public function pastAction()
	{
		return $this->render('SpoutletBundle:Events:past.html.twig', array(
			'events' => $this->getEventsRepo()->getPastEvents($this->getLocale()),
		));
	}

    /**
     * The event show page
     *
     * @param string $slug
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     * @throws \Symfony\Bundle\FrameworkBundle\Controller\NotFoundHttpException
     */
	public function eventAction($slug)
	{
		$event = $this->getEventsRepo()->findOneBy(array(
			'locale' => $this->getLocale(),
			'slug'   => $slug
		));

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
        }

		return $this->render('SpoutletBundle:Events:event.html.twig', array('event' => $event));
	}

	public function unregisterAction($slug)
	{
		$event = $this->getEventsRepo()->findOneBy(array(
			'locale' => $this->getLocale(),
			'slug'   => $slug,
		));

		if (!$event) {
			throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
		}

		if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
			return $this->redirect($this->generateUrl('fos_user_registration_register'));
		}

		$user = $this->get('security.context')->getToken()->getUser();
		$user->removeEvent($event);

		$this->getDoctrine()->getEntityManager()->persist($user);
		$this->getDoctrine()->getEntityManager()->flush();

		return $this->redirect($this->generateUrl('events_detail', array('slug' => $event->getSlug())));
	}

	/**
	 * Registers the current user to an event
	 *
	 * @param string $slug
	 */
	public function registerAction($slug)
	{
		$event = $this->getEventsRepo()->findOneBy(array(
			'locale' => $this->getLocale(),
			'slug'   => $slug
		));

		if (!$event) {
			throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
		}

		if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
			return $this->redirect($this->generateUrl('fos_user_registration_register'));
		}

		$user = $this->get('security.context')->getToken()->getUser();
		$user->addEvent($event);

		$this->getDoctrine()->getEntityManager()->persist($user);
		$this->getDoctrine()->getEntityManager()->flush();

		return $this->redirect($this->generateUrl('events_detail', array('slug' => $event->getSlug())));
	}

	/**
	 * @return Doctrine\Common\Collections\Collection
	 */
	private function getCurrentEvents()
	{
		return $this->getEventsRepo()->getCurrentEvents();
	}

	/**
	 * @return Doctrine\Common\Collections\Collection
	 */
	private function getUpcomingEvents()
	{
		return $this->getEventsRepo()->getUpcomingEvents();
	}
	
	/**
	 * @return Plateformd\SproutletBundle\Entity\EventRepository
	 */
	private function getEventsRepo()
	{
		$em = $this->getDoctrine()->getEntityManager();
		return $em->getRepository('SpoutletBundle:Event');
	}
}