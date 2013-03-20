<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event,
	Platformd\SpoutletBundle\Entity\EventRepository,
	Platformd\SpoutletBundle\Form\Type\EventType,
	Platformd\UserBundle\Entity\User,
    Platformd\MediaBundle\Entity\Media,
    Platformd\MediaBundle\Form\Type\MediaType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Admin controller for events
 */
class AdminController extends Controller
{
    /**
     * Admin homepage
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Admin:index.html.twig');
    }

    public function eventsAction()
    {
        $this->addEventsBreadcrumb();

        $events = $this->getEventsRepo()->findAllWithoutLocaleOrderedByNewest();
    	return $this->render('SpoutletBundle:Admin:events.html.twig',
            array('events' => $events));
    }

    public function newEventAction(Request $request)
    {
        $this->addEventsBreadcrumb()->addChild('New');
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
        $this->addEventsBreadcrumb()->addChild('Edit');
        $event = $this->getEventsRepo()->findOneById($id);

        if (!$event) {
            throw $this->createNotFoundException('No event for that id');
        }

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

    public function approveEventAction($id)
    {
        $repository = $this->getEventsRepo();
        $translator = $this->get('translator');

        if (!$event = $repository->findOneBy(array('id' => $id))) {

            throw $this->createNotFoundException($translator->trans('platformd.events.admin.unkown', array('%event_id%' => $id)));
        }

        $event->setPublished(true);
        $this->getEventsManager()->flush();

        $this->setFlash('success', $translator->trans('platformd.events.admin.approved', array('%event_title%' => $event->getName())));

        return $this->redirect($this->generateUrl('admin_events_index'));
    }

    public function unpublishEventAction($id)
    {
        $translator = $this->get('translator');
        $event = $this->retrieveEvent($id);
        $event->setPublished(false);

        $this->getEventsManager()->flush();

        $this->setFlash('success', $translator->trans('platformd.events.admin.unpublished', array('%event_title%' => $event->getName())));

        return $this->redirect($this->generateUrl('admin_events_index'));
    }

    public function manageMediaAction(Request $request)
    {
        $page   = $request->query->get('page', 1);
        $pager  = $this->getMediaRepo()->getMediaForAdmin(50, $page);
        $medias = $pager->getCurrentPageResults();

        $media  = new Media();
        $form   = $this->createForm(new MediaType(), $media);

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if($form->isValid()) {

                $this->saveMedia($form);
                return $this->redirect($this->generateUrl('admin_upload_media'));
            }
        }

        return $this->render('SpoutletBundle:Admin:manageMedia.html.twig', array(
            'medias' => $medias,
            'pager'   => $pager,
            'form'   => $form->createView(),
        ));
    }

    protected function retrieveEvent($id)
    {

        if (!$event = $this->getEventsRepo()->findOneBy(array('id' => $id))) {

            throw $this->createNotFoundException();
        }

        return $event;
    }
    private function saveEvent($event_form)
    {
        // save to db
        $event = $event_form->getData();

        /*$event->updateBannerImage();

        $em = $this->getEventsManager();
        $em->persist($event);
        $em->flush();*/

        $this
            ->get('platformd.events_manager')
            ->save($event);

        $this->setFlash('success', $this->get('translator')->trans('platformd.events.admin.saved'));
    }

    private function saveMedia($mediaForm)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $media = $mediaForm->getData();
        $media->setOwner($this->getUser());
        $media->setIsAdmin(true);
        $em->persist($media);
        $em->flush();

        $this->setFlash('success', 'Your media was uploaded succesfully');
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\EventRepository
     */
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

    private function getMediaRepo()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('MediaBundle:Media');
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addEventsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Events', array(
            'route' => 'admin_events_index'
        ));

        return $this->getBreadcrumbs();
    }
}
