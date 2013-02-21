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
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException
;

class GlobalEventAdminController extends Controller
{
    /**
     * Lists all global events
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        $this->addEventsBreadcrumb();

        $events = $this->getGlobalEventService()->findBy(array(), array('createdAt' => 'DESC'));

        return $this->render('EventBundle:GlobalEvent\Admin:list.html.twig',
            array('events' => $events));
    }

    /**
     * Create new Global Event
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newEventAction(Request $request)
    {
        $this->addEventsBreadcrumb()->addChild('New');

        $existingEvents = $this->getGlobalEventService()->findAllOwnedEventsForUser($this->getUser());
        $importedGlobalEvent = $this->getGlobalEventService()->findOneBy(array('id' => $request->get('existing_event_select')));

        if ($importedGlobalEvent) {
            return $this->redirect($this->generateUrl('admin_events_new_import', array('id' => $importedGlobalEvent->getId())));
        }

        $event = new GlobalEvent();

        // We add translations by hand
        // TODO improve this
        $siteLocalesForTranslation = array('ja', 'zh', 'es');
        foreach ($siteLocalesForTranslation as $locale) {
            $site = $this->getSiteFromLocale($locale);
            $event->addTranslation(new GlobalEventTranslation($site));
        }

        $form = $this->createForm('globalEvent', $event);

        if($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);

        if($form->isValid())
            {
                $event = $form->getData();
                $event->setUser($this->getUser());

                if ($event->getSites()->count() < 1) {
                    $event->addSite($this->getCurrentSite());
                }

                $this->getGlobalEventService()->createEvent($event);

                $this->setFlash('success', 'New event posted successfully!');

                return $this->redirect($this->generateUrl('admin_events_edit', array('id' => $event->getId())));
            }
        }

        return $this->render('EventBundle:GlobalEvent/Admin:new.html.twig', array(
            'form' => $form->createView(),
            'existingEvents' => $existingEvents,
            'importedGlobalEvent' => $importedGlobalEvent
        ));
    }

    /**
     * Creates an event based on an existing one
     *
     * @param $eventId
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function newFromImportAction($id, Request $request)
    {
        $this->addEventsBreadcrumb()->addChild('import');

        $importedGlobalEvent = $this->getGlobalEventService()->findOneBy(array('id' => $id));

        if (!$importedGlobalEvent) {
            throw new NotFoundHttpException('Event to import from does not exist.');
        }

        $globalEvent = $this->getGlobalEventService()->cloneGlobalEvent($importedGlobalEvent);

        $form = $this->createForm('globalEvent', $globalEvent);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GlobalEvent $globalEvent */
                $globalEvent = $form->getData();
                $globalEvent->setUser($this->getUser());

                $this->getGlobalEventService()->createEvent($globalEvent);

                $this->setFlash('success', 'New event posted successfully!');

                return $this->redirect($this->generateUrl('admin_events_edit', array(
                    'id' => $globalEvent->getId()
                )));

            }
        }

        return $this->render('EventBundle:GlobalEvent/Admin:new.html.twig', array(
            'form' => $form->createView(),
            'importedGlobalEvent' => $importedGlobalEvent
        ));
    }

    public function editEventAction(Request $request, $id)
    {
        $this->addEventsBreadcrumb()->addChild('Edit');
        $event = $this->getGlobalEventService()->find($id);

        if (!$event) {
            throw $this->createNotFoundException('No event for that id');
        }

        $form = $this->createForm('globalEvent', $event);

        if($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);

            if($form->isValid())
            {
                $this->getGlobalEventService()->updateEvent($form->getData());
                $this->setFlash('success', 'Event saved successfully');
                return $this->redirect($this->generateUrl('admin_events_edit', array('id' => $event->getId())));
            }
        }

        return $this->render('EventBundle:GlobalEvent/Admin:edit.html.twig',
            array('form' => $form->createView(), 'event' => $event));
    }

    public function publishEventAction($id)
    {
        $translator = $this->get('translator');

        if (!$event = $this->getGlobalEventService()->find($id)) {

            throw $this->createNotFoundException($translator->trans('platformd.events.admin.unkown', array('%event_id%' => $id)));
        }

        $this->getGlobalEventService()->publishEvent($event);

        $this->setFlash('success', $translator->trans('platformd.events.admin.published', array('%event_title%' => $event->getName())));

        return $this->redirect($this->generateUrl('admin_events_index'));
    }

    public function unpublishEventAction($id)
    {
        $translator = $this->get('translator');

        if (!$event = $this->getGlobalEventService()->find($id)) {

            throw $this->createNotFoundException($translator->trans('platformd.events.admin.unkown', array('%event_id%' => $id)));
        }

        $this->getGlobalEventService()->unpublishEvent($event);

        $this->setFlash('success', $translator->trans('platformd.events.admin.unpublished', array('%event_title%' => $event->getName())));

        return $this->redirect($this->generateUrl('admin_events_index'));
    }

    public function metricsAction(Request $request)
    {
        $results = $this->getGroupEventService()->findGroupEventMetrics('upcoming');

        return $this->render('EventBundle:GlobalEvent\Admin:metrics.html.twig', array(
            'results' => $results,
        ));
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

    /**
     * @return GlobalEventService
     */
    private function getGlobalEventService()
    {
        return $this->get('platformd_event.service.global_event');
    }

        /**
     * @return GroupEventService
     */
    private function getGroupEventService()
    {
        return $this->get('platformd_event.service.group_event');
    }
}
