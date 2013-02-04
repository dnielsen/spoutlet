<?php

namespace Platformd\EventBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
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
//        $user   = $this->getUser();
//        $groups = $this->getGroupManager()->getAllGroupsForUser($user);
//
//        // If User does not belong to a group, we ask them to create one
//        if (count($groups) == 0) {
//            $this->setFlash('error', 'You must join or create a group before you can create an event!');
//
//            return $this->redirect($this->generateUrl('groups'));
//        }
        $group = $this->getGroupManager()->getGroupBy(array('slug' => $slug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $form = $this->createForm('groupEvent', new GroupEvent());

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupEvent = $form->getData();

                $this->getEventService()->createEvent($groupEvent);
                $this->setFlash('success', 'New event posted successfully');

                return $this->redirect($this->generateUrl('group_event_view'));
            } else {
                $this->setFlash('error', 'Something went wrong');
            }
        }

        return $this->render('EventBundle:GroupEvent:new.html.twig', array(
            'form' => $form->createView(),
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

        $groupEventRepo = $this->getGroupEventRepository();
        $groupEvent     = $groupEventRepo->find($id);
        $user           = $this->getUser();

        if (!$groupEvent) {
            $response->setContent(json_encode(array("success" => false, "errorMessage" => "Event not found!")));
            return $response;
        }

        $groupEvent->getAttendees()->removeElement($user);
        $em = $this->getDoctrine()->getEntityManager();

        $em->persist($groupEvent);
        $em->flush();

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
     * @return \Platformd\EventBundle\Service\EventService
     */
    private function getEventService()
    {
        return $this->get('platformd_event.service.group_event');
    }

    private function getGroupEventRepository()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('EventBundle:GroupEvent');
    }
}
