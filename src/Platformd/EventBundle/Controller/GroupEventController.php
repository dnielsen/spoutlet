<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\SpoutletBundle\Entity\Group,
    Platformd\SpoutletBundle\Model\GroupManager
;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\EventBundle\Form\Type\EventType,
    Platformd\EventBundle\Service\EventService,
    Platformd\EventBundle\Entity\GroupEventTranslation
;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
;

use JMS\SecurityExtraBundle\Annotation\Secure;

class GroupEventController extends Controller
{
    /**
     * @Secure(roles="ROLE_USER")
     */
    public function newAction($groupSlug, Request $request)
    {
        /** @var Group $group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = new GroupEvent($group);

        // TODO improve this
        $siteLocalesForTranslation = array('ja', 'zh', 'es');
        foreach ($siteLocalesForTranslation as $locale) {
            $site = $this->getSiteFromLocale($locale);
            $groupEvent->addTranslation(new GroupEventTranslation($site, $groupEvent));
        }

        $form = $this->createForm('groupEvent', $groupEvent);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GroupEvent $groupEvent */
                $groupEvent = $form->getData();
                $groupEvent->setUser($this->getUser());

                $this->getGroupEventService()->createEvent($groupEvent);
                $this->setFlash('success', 'New event posted successfully');

                return $this->redirect($this->generateUrl('group_event_view', array(
                    'groupSlug' => $group->getSlug(),
                    'eventSlug' => $groupEvent->getSlug()
                )));
            } else {
                $this->setFlash('error', 'Something went wrong');
            }
        }

        return $this->render('EventBundle:GroupEvent:new.html.twig', array(
            'form' => $form->createView(),
            'group' => $group
        ));
    }

    /**
     * Only event owner can edit their event
     */
    public function editAction($groupSlug, $eventId, Request $request)
    {
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'id' => $eventId
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        $securityContext = $this->getSecurity();

        // check for edit access
        if (false === $securityContext->isGranted('EDIT', $groupEvent))
        {
            throw new AccessDeniedException();
        }

        $form = $this->createForm('groupEvent', $groupEvent);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                /** @var GroupEvent $groupEvent */
                $groupEvent = $form->getData();
                $groupEvent->setUser($this->getUser());

                $this->getGroupEventService()->updateEvent($groupEvent);
                $this->setFlash('success', 'New event posted successfully');

                return $this->redirect($this->generateUrl('group_event_edit', array(
                    'groupSlug' => $group->getSlug(),
                    'eventId' => $groupEvent->getId()
                )));
            } else {
                $this->setFlash('error', 'Something went wrong');
            }
        }

        return $this->render('EventBundle:GroupEvent:edit.html.twig', array(
            'form' => $form->createView(),
            'group' => $group,
            'event' => $groupEvent
        ));
    }

    public function viewAction($groupSlug, $eventSlug)
    {
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
            'published' => true,
            'deleted' => false,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        $isAttending = $this->getGroupEventService()->isUserAttending($groupEvent, $this->getUser());

        $attendeeCount = $this->getGroupEventService()->getAttendeeCount($groupEvent);

        return $this->render('EventBundle::view.html.twig', array(
            'group'         => $group,
            'event'         => $groupEvent,
            'attendeeCount' => $attendeeCount,
            'isAttending'   => $isAttending,
        ));
    }

    public function contactAction($groupSlug, $eventSlug)
    {
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
            'published' => true,
            'deleted' => false,
            'approved' => true,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        return $this->render('EventBundle::contact.html.twig', array(
            'group'         => $group,
            'event'         => $groupEvent,
        ));
    }

    /**
     * Lists all events pending approval
     * Only for group owner
     *
     * @param $groupSlug
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function pendingApprovalListAction($groupSlug)
    {
        /** @var $group Group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        if (!$group->isAllowedTo($this->getUser(), $this->getCurrentSite(), 'ApproveEvent')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        $pendingApprovals = $this->getGroupEventService()->getPendingApprovalEvents($group, $this->getUser());

        return $this->render('EventBundle:GroupEvent:pending.html.twig', array(
            'pendingApprovals' => $pendingApprovals,
            'group' => $group
        ));
    }

    public function rsvpAjaxAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['id']) || !isset($params['rsvp'])) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $id     = (int) $params['id'];
        $rsvp   = $params['rsvp'];

        if (!$this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => 'You must be logged in to RSVP to an event')));
            return $response;
        }

        $groupEvent = $this->getGroupEventService()->find($id);
        $user       = $this->getUser();

        if (!$groupEvent) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Event not found!")));
            return $response;
        }

        $isAttending = $this->getGroupEventService()->isUserAttending($groupEvent, $user);

        if ($rsvp == 0 && $isAttending) {
             $groupEvent->getAttendees()->removeElement($user);
             $this->getGroupEventService()->updateEvent($groupEvent);
        } elseif ($rsvp > 0 && !$isAttending) {
            $groupEvent->getAttendees()->add($user);
            $this->getGroupEventService()->updateEvent($groupEvent);
        }

        $attendeeCount = $this->getGroupEventService()->getAttendeeCount($groupEvent);

        $response->setContent(json_encode(array("success" => true, "attendeeCount" => $attendeeCount)));
        return $response;
    }

    public function disableAjaxAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['id'])) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Some required information was not passed.")));
            return $response;
        }

        $id = (int) $params['id'];

        if (!$this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => 'You must be logged in to delete an event')));
            return $response;
        }

        $groupEvent = $this->getGroupEventService()->find($id);
        $user           = $this->getUser();

        if (!$groupEvent) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Event not found!")));
            return $response;
        }

        if ($user != $groupEvent->getUser() || $user->getAdminLevel() === null) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "You are not authorized to delete this event.")));
            return $response;
        }

        $groupEvent->setPublished(false);

        $this->getGroupEventService()->updateEvent($groupEvent);

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    /**
     * @return GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

    /**
     * @return \Platformd\EventBundle\Service\GroupEventService
     */
    private function getGroupEventService()
    {
        return $this->get('platformd_event.service.group_event');
    }
}
