<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\SpoutletBundle\Entity\Group,
    Platformd\SpoutletBundle\Model\GroupManager
;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\EventBundle\Form\Type\EventType,
    Platformd\EventBundle\Service\EventService
;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException
;

use JMS\SecurityExtraBundle\Annotation\Secure;

class GroupEventController extends Controller
{
    /**
     * @Secure(roles="ROLE_USER")
     */
    public function newAction($slug, Request $request)
    {
        /** @var Group $group */
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $slug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = new GroupEvent($group);

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

    public function editAction($groupSlug, $eventSlug, Request $request)
    {
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
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
                    'eventSlug' => $groupEvent->getSlug()
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
            'slug' => $eventSlug
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        return $this->render('EventBundle:GroupEvent:view.html.twig', array(
            'group' => $group,
            'event' => $groupEvent
        ));
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
