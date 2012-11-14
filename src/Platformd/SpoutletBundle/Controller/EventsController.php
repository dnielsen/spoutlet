<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event,
    Platformd\SpoutletBundle\Entity\EventRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EventsController extends Controller
{
    public function indexAction()
    {
        $current_events = $this->getAbstractEventsRepo()
            ->getCurrentEventsAndSweepstakes($this->getCurrentSite(), 50);
        $past_events    = $this->getAbstractEventsRepo()
            ->getPastEventsAndSweepstakes($this->getCurrentSite(), 50);

        $allGiveaways = $this->getGiveawayRepo()
            ->findActives($this->getCurrentSite())
        ;

        return $this->render('SpoutletBundle:Events:index.html.twig',
            array(
                'current_events' => $current_events,
                'past_events'    => $past_events,
                'giveaways'      => $allGiveaways,
            ));
    }

    public function currentAction()
    {
        return $this->render('SpoutletBundle:Events:current.html.twig', array(
            'events' => $this->getEventsRepo()->getCurrentEvents($this->getCurrentSite()),
        ));
    }

    public function pastAction()
    {
        return $this->render('SpoutletBundle:Events:past.html.twig', array(
            'events' => $this->getEventsRepo()->getPastEvents($this->getCurrentSite()),
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
        /*
         * Notice that this does *not* respect "published". This is on purpose,
         * because the client wants to be able to preview events to the client
         */
        $event = $this->getEventsRepo()->findOneBySlug($slug, $this->getCurrentSite());

        if (!$event) {
            throw $this->createNotFoundException(sprintf('No event for slug "%s"', $slug));
        }

        // if we have a url redirect, then we should never get to this page
        if ($event->getUrlRedirect()) {
            return new RedirectResponse($event->getUrlRedirect());
        }

        return $this->render('SpoutletBundle:Events:event.html.twig', array('event' => $event));
    }

    public function unregisterAction($slug)
    {
        $event = $this->getEventsRepo()->findOneBySlug($slug, $this->getCurrentSite());

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
        $event = $this->getEventsRepo()->findOneBySlug($slug, $this->getCurrentSite());

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
     * @return \Platformd\SpoutletBundle\Entity\EventRepository
     */
    private function getEventsRepo()
    {
        $em = $this->getDoctrine()->getEntityManager();
        return $em->getRepository('SpoutletBundle:Event');
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\AbstractEventRepository
     */
    private function getAbstractEventsRepo()
    {
        $em = $this->getDoctrine()->getEntityManager();
        return $em->getRepository('SpoutletBundle:AbstractEvent');
    }
}
