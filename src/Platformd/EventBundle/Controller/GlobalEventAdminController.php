<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\EventBundle\Service\GlobalEventService,
    Platformd\EventBundle\Entity\GlobalEvent,
    Platformd\EventBundle\Form\Type\GlobalEventType,
    Platformd\EventBundle\Entity\GlobalEventTranslation,
    Platformd\EventBundle\Entity\EventFindWrapper,
    Platformd\EventBundle\Form\Type\EventFindType,
    Platformd\SpoutletBundle\Util\CsvResponseFactory;
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
        $page = $request->query->get('page', 1);

        $this->resetEventsFilterFormData();

        $data = new EventFindWrapper();
        $form = $this->createForm(new EventFindType(), $data);

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                $this->setEventsFilterFormData(array(
                    'eventName' => $data->getEventName(),
                    'published' => $data->getPublished(),
                    'sites' => $data->getSites(),
                    'from' => $data->getFrom(),
                    'thru' => $data->getThru(),
                    'eventType' => $data->getEventType(),
                ));
            }
        }

        if($data->getEventType() == 'global') {
            $pager = $this->getGlobalEventService()->findGlobalEventStats(array(
                'eventName' => $data->getEventName(),
                'published' => $data->getPublished(),
                'sites' => $data->getSites(),
                'from' => $data->getFrom(),
                'thru' => $data->getThru(),
                'page' => $page
            ));
        } else {
            $pager = $this->getGroupEventService()->findGroupEventStats(array(
                'eventName' => $data->getEventName(),
                'published' => $data->getPublished(),
                'sites' => $data->getSites(),
                'from' => $data->getFrom(),
                'thru' => $data->getThru(),
                'page' => $page
            ));

        }

        return $this->render('EventBundle:GlobalEvent\Admin:metrics.html.twig', array(
            'pager'    => $pager,
            'form'     => $form->createView(),
            'typeParam'=> $data->getEventType() ?: 'group'
        ));
    }

    public function eventSummaryCsvAction()
    {
        return $this->generateSummaryCsv();
    }

    private function generateSummaryCsv()
    {
        $factory = new CsvResponseFactory();

        $factory->addRow(array(
            'Event Title',
            'Status',
            'Group',
            'Event Organizer',
            'Region',
            'Start Date',
            'End Date',
            '# of Attendees',
            'Game'
        ));

        $filters = array_merge(
            array('eventName' => '','sites' => array(), 'filter' => ''),
            $this->getEventsFilterFormData()
        );

        if($filters['eventType'] == 'global') {
            $results = $this->getGlobalEventService()->findGlobalEventStats($filters);
        } else {
            $results = $this->getGroupEventService()->findGroupEventStats($filters);
        }

        foreach ($results as $result) {
            $region = '';

            foreach ($result->getSites() as $site) {
                $region .=  '['.$site->getName().']';
            }

            $status = $result->getPublished() ? 'Active' : 'Inactive';
            $groupName = $result->getGroup() ? $result->getGroup()->getName() : 'N/A';
            $organizer = !$result->getGroup() ? $result->getHostedBy() : 'N/A';

            $factory->addRow(array(
                $result->getName(),
                $status,
                $groupName,
                $organizer,
                $region,
                $result->getStartsAt()->format('Y-m-d H:i:s'),
                $result->getEndsAt()->format('Y-m-d H:i:s'),
                $result->getAttendeeCount(),
                $result->getGame()
            ));
        }

        return $factory->createResponse('Event_Summary.csv');
    }

    public function eventAttendeeCsvAction($id, $eventType)
    {
        if($eventType == 'global') {
            $service = $this->getGlobalEventService();
        } else {
            $service = $this->getGroupEventService();
        }

        $event   = $service->find($id);

        if(!$event) {
            return $this->redirect($this->generateUrl('admin_event_metrics'));
        }

        $attendees = $service->getAttendeeList($event);

        return $this->generateAttendeeCsv($attendees, $event->getName());
    }

    private function generateAttendeeCsv($attendees, $eventName)
    {
        $factory = new CsvResponseFactory();

        $factory->addRow(array(
            'Username',
            'Email',
            'RSVP Date'
        ));

        foreach ($attendees as $attendee) {
            $factory->addRow(array(
                $attendee['username'],
                $attendee['email'],
                'N/A'
            ));
        }

        $fileName = sprintf('%s_Attendees.csv', $eventName);

        return $factory->createResponse($fileName);
    }

    private function resetEventsFilterFormData()
    {
        $this->setEventsFilterFormData(array());
    }

    private function getEventsFilterFormData()
    {
        $session = $this->getRequest()->getSession();
        return $session->get('eventsFormValues', array());
    }

    private function setEventsFilterFormData($data)
    {
        $session = $this->getRequest()->getSession();
        $session->set('eventsFormValues', $data);
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

    /**
     * @return GroupEventService
     */
    private function getEventService()
    {
        return $this->get('platformd_event.service.event');
    }
}
