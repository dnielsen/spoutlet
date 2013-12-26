<?php

namespace Platformd\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\Request;

use Platformd\SpoutletBundle\Controller\Controller;



class ApiController extends Controller
{
    public function entrySetAction(Request $request, $entrySetId)
    {
        $entrySet = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->find($entrySetId);

        $response = new Response();

        if (!$entrySet){
            $response->setStatusCode(404);
        }
        else {
            $response->setStatusCode(200);

            $entries = array();
            foreach ($entrySet->getEntries() as $entry) {
                $entries[] = array(
                    'id'    => $entry->getId(),
                    'name'  => $entry->getName(),
                );
            }

            $entrySetData = array(
                'meta'                 => array(
                        'self'  => $this->generateUrl('api_entrySet', array('entrySetId'=>$entrySet->getId()), true),
                        'mimetype' => "application/json"
                    ),
                'id'                    => $entrySet->getId(),
                'name'                  => $entrySet->getName(),
                'type'                  => $entrySet->getType(),
                'registrationId'        => $entrySet->getEntrySetRegistration()->getId(),
                'isSubmissionsActive'   => $entrySet->getIsSubmissionActive(),
                'isVotingActive'        => $entrySet->getIsVotingActive(),
                'allowedVoters'         => $entrySet->getAllowedVoters(),
                'entries'               => $entries,
            );

            $encoder = new JsonEncoder();
            $jsonData = $encoder->encode($entrySetData, $format = 'json');
            $response->setContent($this->jsonpWrapper($request,$jsonData));
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }

    public function entryAction(Request $request, $entryId)
    {
        $entry = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($entryId);

        $response = new Response();

        if (!$entry){
            $response->setStatusCode(404);
        }
        else {
            $response->setStatusCode(200);

            $entryData = array(
                'meta'                 => array(
                    'self'  => $this->generateUrl('api_entry', array('entryId'=>$entry->getId()), true),
                    'mimetype' => "application/json"
                ),
                'id'                    => $entry->getId(),
                'entrySetId'            => $entry->getEntrySet()->getId(),
                'creator'               => $entry->getCreator()->getUserName(),
                'createdAt'             => $entry->getCreatedAt(),
                'name'                  => $entry->getName(),
                'description'           => $entry->getDescription(),
                'numVotes'              => $entry->getNumVotes(),
            );

            $encoder = new JsonEncoder();
            $jsonData = $encoder->encode($entryData, $format = 'json');
            $response->setContent($this->jsonpWrapper($request,$jsonData));
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }

    public function groupAction(Request $request, $groupId)
    {
        $group = $this->getGroupManager()->find($groupId);

        $response= new Response();

        if (!$group){
            return $response->setStatusCode(404);
        }

        $response->setStatusCode(200);

        $entrySets = array();
        foreach ($group->getEntrySets() as $entrySet) {

            $sortedEntries = $entrySet->getEntries()->toArray();
            $this->getDoctrine()->getRepository('IdeaBundle:Idea')->sortByFollows($sortedEntries);

            $entries = array();
            foreach ($sortedEntries as $entry) {
                $entries[] = array(
                    'id'        => $entry->getId(),
                    'name'      => $entry->getName(),
                    'numVotes'  => $entry->getNumVotes(),
                    'url'       => $this->generateUrl($entry->getLinkableRouteName(), $entry->getLinkableRouteParameters(), true),
                );
            }
            $entrySets[] = array(
                'id'        => $entrySet->getId(),
                'name'      => $entrySet->getName(),
                'url'       => $this->generateUrl($entrySet->getLinkableRouteName(), $entrySet->getLinkableRouteParameters(), true),
                'entries'   => $entries,
            );
        }
        $upcomingEvents = $this->getGroupEventService()->findUpcomingEventsForGroupMostRecentFirst($group, 1);
        $nextEvent = reset($upcomingEvents);
        $pastEvents = $this->getGroupEventService()->findPastEventsForGroupMostRecentFirst($group, 6);

        $pastEventData = array();
        foreach ($pastEvents as $event) {
            $pastEventData[] = array(
                'id'        => $event->getId(),
                'name'      => $event->getName(),
                'daterange' => $event->getDateRangeString(),
                'timerange' => $event->getStartsAt()->format('g:i a').' - '.$event->getEndsAt()->format('g:i a'),
                'url'       => $this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
            );
        }
        $nextEvent = array(
            'id'            => $nextEvent->getId(),
            'name'          => $nextEvent->getName(),
            'daterange'     => $nextEvent->getDateRangeString(),
            'timerange'     => $event->getStartsAt()->format('g:i a').' - '.$event->getEndsAt()->format('g:i a'),
            'location'      => $nextEvent->getLocation(),
            'address1'      => $nextEvent->getAddress1(),
            'address2'      => $nextEvent->getAddress2(),
            'url'           => $this->generateUrl($nextEvent->getLinkableRouteName(), $nextEvent->getLinkableRouteParameters(), true),
        );


        $groupData = array(
            'meta'                 => array(
                'self'  => $this->generateUrl('api_group', array('groupId'=>$group->getId()), true),
                'mimetype' => "application/json"
            ),
            'id'                    => $group->getId(),
            'creator'               => $group->getOwner()->getUserName(),
            'name'                  => $group->getName(),
            'description'           => $group->getDescription(),
            'pastEvents'            => $pastEventData,
            'nextEvent'             => $nextEvent,
            'entrySets'             => $entrySets,
        );

        $encoder = new JsonEncoder();
        $jsonData = $encoder->encode($groupData, $format = 'json');
        $response->setContent($this->jsonpWrapper($request,$jsonData));
        $response->headers->set('Content-Type', 'application/json');


        return $response;
    }

    public function jsonpWrapper(Request $request, $jsonData)
    {
        $callback = $request->query->get('callback');
        if(!$callback)
            return $jsonData;

        return $callback . "(" . $jsonData . ")";
    }

}

?>