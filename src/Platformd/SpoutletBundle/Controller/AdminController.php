<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event,
	Platformd\SpoutletBundle\Entity\EventRepository,
	Platformd\SpoutletBundle\Form\Type\EventType,
	Platformd\UserBundle\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends Controller
{
    
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Admin:index.html.twig');
    }

    public function eventsAction() 
    {
        $events = $this->getEventsRepo()->findAll();
    	return $this->render('SpoutletBundle:Admin:events.html.twig', 
            array('events' => $events));
    }

    public function newEventAction(Request $request) 
    {
    	$event = new Event();

    	$form = $this->createForm(new EventType(), $event);

    	if($request->getMethod() == 'POST')
    	{
    		$form->bindRequest($request);

    		if($form->isValid())
    		{
    			$this->saveEvent($form);
    			return $this->redirect($this->generateUrl('admin_events_edit', array('id' => $event->getId())));
    		}
    	}

    	return $this->render('SpoutletBundle:Admin:newEvent.html.twig', array('form' => $form->createView(),));
    }

    public function editEventAction(Request $request, $id)
    {
        $event = $this->getEventsRepo()->findOneById($id);
        $form = $this->createForm(new EventType(), $event);

        if($request->getMethod() == 'POST')
        {
        	$form->bindRequest($request);

        	if($form->isValid())
        	{
        		$this->saveEvent($form);
        		return $this->redirect($this->generateUrl('admin_events_edit', array('id' => $event->getId())));
        	}
        }

    	return $this->render('SpoutletBundle:Admin:editEvent.html.twig', 
    		array('form' => $form->createView(), 'event' => $event));
    }

    private function saveEvent($event_form)
    {
    	// save to db
    	$user = $this->get('security.context')->getToken()->getUser();
    	$event = $event_form->getData();
    	$event->setUser($user);
    	$em = $this->getEventsManager();
    	$em->persist($event);
    	$em->flush();
    }

    private function getEventsRepo()
    {
        return $this->getEventsManager()
            ->getRepository('SpoutletBundle:Event');
    }

    private function getEventsManager()
    {
        return $this->getDoctrine()
            ->getEntityManager();
    }
}
