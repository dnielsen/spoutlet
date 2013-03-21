<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event,
	Platformd\SpoutletBundle\Entity\EventRepository,
	Platformd\SpoutletBundle\Form\Type\EventType,
	Platformd\UserBundle\Entity\User;

use Symfony\Component\HttpFoundation\Request;

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

    public function massUnsubscribeAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $form = $this->createFormBuilder()
            ->add('emailsText', 'textarea', array(
                'attr' => array(
                    'class' => 'input-xlarge'
                ),
                'label' => 'Paste Emails',
                'required' => false,
            ))
            ->add('emailsFile', 'file', array(
                'label' => 'Upload Emails (CSV)',
                'required' => false,
            ))
            ->getForm();

        $emailCount = null;

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $data = $form->getData();

                $emailsText = $data['emailsText'];
                $emails  = $emailsText ? explode(',', $emailsText) : array();

                foreach ($emails as $index => $email) {
                    $emails[$index] = trim($email);
                }

                $file = $form->get('emailsFile');
                $file = $file->getData();

                if ($file && ($handle = fopen($file, "r")) !== FALSE) {

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (!in_array($data[0], $emails) && $data[0] != "") {
                            $emails[] = $data[0];
                        }
                    }

                    fclose($handle);
                }

                if (count($emails) == 0) {
                    $this->setFlash('error', 'No email addresses submitted!');
                    return $this->redirect($this->generateUrl('admin_mass_unsubscribe'));
                }

                $users = $em->getRepository('UserBundle:User')->findUserListByEmail($emails);

                if ($users) {
                    foreach ($users as $user) {
                        $user->setSubscribedAlienwareEvents(false);
                        $em->persist($user);
                    }

                    $em->flush();
                }

                $emailCount = count($users);

                $this->setFlash('success', sprintf('%d members successfully unsubscribed!', $emailCount));
                return $this->redirect($this->generateUrl('admin_mass_unsubscribe'));
            }
        }

        return $this->render('SpoutletBundle:Admin:unsubscribe.html.twig', array(
            'form' => $form->createView(),
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
