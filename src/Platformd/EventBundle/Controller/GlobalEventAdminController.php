<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\EventBundle\Service\GlobalEventService,
    Platformd\EventBundle\Entity\GlobalEvent,
    Platformd\EventBundle\Form\Type\GlobalEventType,
    Platformd\EventBundle\Entity\GlobalEventTranslation,
    Platformd\EventBundle\Form\Type\EventFindType,
    Platformd\SpoutletBundle\Util\CsvResponseFactory
;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException
;

use Doctrine\Common\Collections\ArrayCollection;

class GlobalEventAdminController extends Controller
{
    public function indexAction()
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $url = $this->generateUrl('admin_events_list', array('site' => 2));
            return $this->redirect($url);
        }

        $this->addEventsBreadcrumb();

        return $this->render('EventBundle:GlobalEvent\Admin:index.html.twig', array(
            'sites' => $this->getSiteManager()->getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $site = 2;
        }

        $this->addEventsBreadcrumb();
        $this->addSiteBreadcrumbs($site);

        $site = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Site')->find($site);

        $events = $this->getGlobalEventService()->findAllForSite($site);

        return $this->render('EventBundle:GlobalEvent\Admin:list.html.twig', array(
            'events'    => $events,
            'site'      => $site,
        ));
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

        $existingEvents         = $this->getGlobalEventService()->findAllOwnedEventsForUser($this->getUser());
        $importedGlobalEvent    = $this->getGlobalEventService()->findOneBy(array('id' => $request->get('existing_event_select')));
        $tagManager             = $this->getTagManager();

        if ($importedGlobalEvent) {
            return $this->redirect($this->generateUrl('admin_events_new_import', array('id' => $importedGlobalEvent->getId())));
        }

        $event = new GlobalEvent();

        // We add translations by hand
        // TODO improve this
        $siteLocalesForTranslation = array('ja', 'zh', 'es');
        foreach ($siteLocalesForTranslation as $locale) {
            $site = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($locale);
            if ($site) {
                $event->addTranslation(new GlobalEventTranslation($site));
            }
        }

        $form = $this->createForm('globalEvent', $event);

        if($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);

            if($form->isValid())
            {
                $em = $this->getDoctrine()->getEntityManager();

                $event = $form->getData();
                    
                $event->setUser($this->getCurrentUser());
                $event->setTimezone('UTC');
                $event->setActive(true);
                $event->setApproved(true);
                $event->setPublished(true);
                $event->setRegistrationOption('REGISTRATION_DISABLED');
                
                $event->addSite($this->getCurrentSite());
                
                //$tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));

                $this->getGlobalEventService()->createEvent($event);

                //$tagManager->addTags($tags, $event);
                //$tagManager->saveTagging($event);

                // Registration needs to be created after event is persisted, relies on generated event ID
                $esReg = $event->createEntrySetRegistration();
                $em->persist($esReg);

                $this->setFlash('success', 'New event posted successfully!');

                return $this->redirect($this->generateUrl('global_events_index'));
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

        $tagManager = $this->getTagManager();
        $event      = $this->getGlobalEventService()->find($id);

        if (!$event) {
            throw $this->createNotFoundException('No event for that id');
        }

        $tagManager->loadTagging($event);

        $form = $this->createForm('globalEvent', $event);

        if($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);

            if($form->isValid())
            {
                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));

                $this->getGlobalEventService()->updateEvent($event);

                $tagManager->addTags($tags, $event);

                $tagManager->saveTagging($event);

                $this->setFlash('success', 'Event saved successfully');
                return $this->redirect($this->generateUrl('admin_events_edit', array('id' => $event->getId())));
            }
        }

        return $this->render('EventBundle:GlobalEvent/Admin:edit.html.twig',
            array('form' => $form->createView(), 'event' => $event));
    }

    public function publishEventAction($id)
    {
        if (!$event = $this->getGlobalEventService()->find($id)) {

            throw $this->createNotFoundException($this->trans('platformd.events.admin.unkown', array('%event_id%' => $id)));
        }

        $this->getGlobalEventService()->publishEvent($event);

        $this->setFlash('success', $this->trans('platformd.events.admin.published', array('%event_title%' => $event->getName())));

        return $this->redirect($this->generateUrl('admin_events_index'));
    }

    public function unpublishEventAction($id)
    {
        if (!$event = $this->getGlobalEventService()->find($id)) {

            throw $this->createNotFoundException($this->trans('platformd.events.admin.unkown', array('%event_id%' => $id)));
        }

        $this->getGlobalEventService()->unpublishEvent($event);

        $this->setFlash('success', $this->trans('platformd.events.admin.unpublished', array('%event_title%' => $event->getName())));

        return $this->redirect($this->generateUrl('admin_events_index'));
    }

    public function metricsAction(Request $request)
    {
        if ($request->query->get('reset')) {
            $this->resetEventsFilterFormData();
        }

        $page  = $request->query->get('page', 1);
        $em    = $this->getDoctrine()->getEntityManager();
        $japan = $em->getRepository('SpoutletBundle:Site')->find(2);

        $data = array(
            'eventName' => null,
            'published' => null,
            'eventType' => null,
            'sites'     => null,
            'from'      => null,
            'thru'      => null,
        );
        $form = $this->createForm(new EventFindType(), $data);

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $data['sites'] = new ArrayCollection(array($japan));
            $form->setData($data);
        }

        if ('POST' == $request->getMethod()) {

            $form->bindRequest($request);
            if ($form->isValid()) {

                $data = $form->getData();

                if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
                    $data['sites'] = new ArrayCollection(array($japan));
                    $form->setData($data);
                }

                $this->setEventsFilterFormData($data);
            }
        } else {
            $sessionData = $this->getEventsFilterFormData();
            if (count($sessionData) > 0) {
                $data = $sessionData;

                if (count($data['sites']) > 0) {
                    foreach ($data['sites'] as $site) {
                        $site = $em->merge($site);
                        $sites[] = $site;
                    }

                    $data['sites'] = new ArrayCollection($sites);
                }

                $form->setData($data);
            }
        }

        $sites = array();

        if (count($data['sites']) > 0) {
            foreach ($data['sites'] as $site) {
                $sites[] = $site;
            }

            if (count($sites) > 0) {
                $data['sites'] = $sites;
            }
        }

        if($data['eventType'] == 'global') {
            $pager = $this->getGlobalEventService()->findGlobalEventStats(array_merge(array('page' => $page), $data));
        } else {
            $pager = $this->getGroupEventService()->findGroupEventStats(array_merge(array('page' => $page), $data));
        }

        return $this->render('EventBundle:GlobalEvent\Admin:metrics.html.twig', array(
            'pager'    => $pager,
            'form'     => $form->createView(),
            'typeParam'=> $data['eventType'] ?: 'group'
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

        if($attendees) {
            foreach ($attendees as $attendee) {
                $factory->addRow(array(
                    $attendee['username'],
                    $attendee['email'],
                    $attendee['rsvpAt']
                ));
            }
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

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild($this->getSiteManager()->getSiteName($site), array(
                'route' => 'admin_events_list',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }

    /**
     * @return GroupEventService
     */
    private function getEventService()
    {
        return $this->get('platformd_event.service.event');
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }
}
