<?php

namespace Platformd\ApiBundle\Controller;

use Platformd\EventBundle\Entity\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\Request;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\GroupBundle\Entity\Group;
use Platformd\EventBundle\Entity\GroupEvent;

class ApiController extends Controller
{

    public function entrySetAction(Request $request, $entrySetId)
    {
        $entrySet = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->find($entrySetId);

        $response = new Response();

        if (!$entrySet) {
            $response->setStatusCode(404);
        } else {
            $response->setStatusCode(200);

            $entries = array();
            foreach ($entrySet->getEntries() as $entry) {
                $entries[] = array(
                    'id' => $entry->getId(),
                    'name' => $entry->getName(),
                );
            }

            $entrySetData = array(
                'meta' => array(
                    'self' => $this->generateUrl('api_entrySet', array('entrySetId' => $entrySet->getId()), true),
                    'mimetype' => "application/json"
                ),
                'id' => $entrySet->getId(),
                'name' => $entrySet->getName(),
                'type' => $entrySet->getType(),
                'registrationId' => $entrySet->getEntrySetRegistration()->getId(),
                'isSubmissionsActive' => $entrySet->getIsSubmissionActive(),
                'isVotingActive' => $entrySet->getIsVotingActive(),
                'allowedVoters' => $entrySet->getAllowedVoters(),
                'entries' => $entries,
            );

            $encoder = new JsonEncoder();
            $jsonData = $encoder->encode($entrySetData, 'json');
            $response->setContent($this->jsonpWrapper($request, $jsonData));
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }

    public function entryAction(Request $request, $entryId)
    {
        $entry = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($entryId);

        $response = new Response();

        if (!$entry) {
            $response->setStatusCode(404);
        } else {
            $response->setStatusCode(200);

            $entryData = array(
                'meta' => array(
                    'self' => $this->generateUrl('api_entry', array('entryId' => $entry->getId()), true),
                    'mimetype' => "application/json"
                ),
                'id' => $entry->getId(),
                'entrySetId' => $entry->getEntrySet()->getId(),
                'creator' => $entry->getCreator()->getUserName(),
                'createdAt' => $entry->getCreatedAt(),
                'name' => $entry->getName(),
                'description' => $entry->getDescription(),
                'numVotes' => $entry->getNumVotes(),
            );

            $encoder = new JsonEncoder();
            $jsonData = $encoder->encode($entryData, 'json');
            $response->setContent($this->jsonpWrapper($request, $jsonData));
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }

    public function eventAction(Request $request, $eventId)
    {
        $event = $this->getGroupEventService()->find($eventId);

        $response = new Response();
        if (!$event) {
            return $response->setStatusCode(404);
        }

        $response->setStatusCode(200);

        $entrySets = $this->getEntrySets($event);

        $eventData = array(
            'meta' => array(
                'self' => $this->generateUrl('api_event', array('eventId' => $event->getId()), true),
                'mimetype' => "application/json"
            ),
            'id' => $event->getId(),
            'creator' => $event->getUser()->getUserName(),
            'name' => $event->getName(),
            'description' => $event->getContent(),
            'daterange' => $event->getDateRangeString(),
            'timerange' => $event->getStartsAt()->format('g:i a') . ' - ' . $event->getEndsAt()->format('g:i a'),
            'location' => $event->getLocation(),
            'address1' => $event->getAddress1(),
            'address2' => $event->getAddress2(),
            'entrySets' => $entrySets,
        );

        $encoder = new JsonEncoder();
        $jsonData = $encoder->encode($eventData, 'json');
        $response->setContent($this->jsonpWrapper($request, $jsonData));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function groupAction(Request $request, $groupId)
    {
        $group = $this->getGroupManager()->find($groupId);

        $response = new Response();

        if (!$group) {
            return $response->setStatusCode(404);
        }

        $response->setStatusCode(200);

        $entrySets = $this->getEntrySets($group);

        $upcomingEvents = $this->getGroupEventService()->findUpcomingEventsForGroupMostRecentFirst($group);
        $nextEvent = reset($upcomingEvents);
        $pastEvents = $this->getGroupEventService()->findPastEventsForGroupMostRecentFirst($group, 6);

        $pastEventData = array();
        foreach ($pastEvents as $event) {
            $pastEventData[] = array(
                'id' => $event->getId(),
                'name' => $event->getName(),
                'daterange' => $event->getDateRangeString(),
                'timerange' => $event->getStartsAt()->format('g:i a') . ' - ' . $event->getEndsAt()->format('g:i a'),
                'location' => $event->getLocation(),
                'address1' => $event->getAddress1(),
                'address2' => $event->getAddress2(),
                'url' => $this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
            );
        }

        $upcomingEventData = array();
        foreach ($upcomingEvents as $event) {
            $upcomingEventData[] = array(
                'id' => $event->getId(),
                'name' => $event->getName(),
                'daterange' => $event->getDateRangeString(),
                'timerange' => $event->getStartsAt()->format('g:i a') . ' - ' . $event->getEndsAt()->format('g:i a'),
                'location' => $event->getLocation(),
                'address1' => $event->getAddress1(),
                'address2' => $event->getAddress2(),
                'url' => $this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
            );
        }

        if ($nextEvent != null) {
            $nextEvent = array(
                'id' => $nextEvent->getId(),
                'name' => $nextEvent->getName(),
                'daterange' => $nextEvent->getDateRangeString(),
                'timerange' => $nextEvent->getStartsAt()->format('g:i a') . ' - ' . $nextEvent->getEndsAt()->format('g:i a'),
                'location' => $nextEvent->getLocation(),
                'address1' => $nextEvent->getAddress1(),
                'address2' => $nextEvent->getAddress2(),
                'url' => $this->generateUrl($nextEvent->getLinkableRouteName(), $nextEvent->getLinkableRouteParameters(), true),
            );
        }

        $avatarPath = null;
        $avatar = $group->getGroupAvatar();
        if ($avatar) {
            $mediaResolver = $this->getMediaPathResolver();
            $avatarPath = $mediaResolver->getPath($group->getGroupAvatar(), array());
        }

        $childrenGroupData = array();
        $parentGroupData = array();
        if ($group->getCategory() == Group::CAT_TOPIC) {
            $childrenGroups = $group->getChildren();
            foreach ($childrenGroups as $child) {
                $childrenGroupData[] = array(
                    'id' => $child->getId(),
                    'creator' => $child->getOwner()->getUserName(),
                    'name' => $child->getName(),
                    'url' => $this->generateUrl($child->getLinkableRouteName(), $child->getLinkableRouteParameters(), true),
                );
            }
        } else {
            $parent = $group->getParent();
            if ($parent) {
                $parentGroupData['id'] = $parent->getId();
                $parentGroupData['name'] = $parent->getName();
                $parentGroupData['creator'] = $parent->getOwner()->getUserName();
                $parentGroupData['url'] = $this->generateUrl($parent->getLinkableRouteName(), $parent->getLinkableRouteParameters(), true);
            }
        }
        $groupData = array(
            'meta' => array(
                'self' => $this->generateUrl('api_group', array('groupId' => $group->getId()), true),
                'mimetype' => "application/json"
            ),
            'id' => $group->getId(),
            'creator' => $group->getOwner()->getUserName(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'avatarPath' => $avatarPath ? $avatarPath : null,
            'url' => $this->generateUrl($group->getLinkableRouteName(), $group->getLinkableRouteParameters(), true),
            'childrenGroups' => $childrenGroupData,
            'parentGroup' => $parentGroupData,
            'pastEvents' => $pastEventData,
            'upcomingEvents' => $upcomingEventData,
            'nextEvent' => $nextEvent,
            'entrySets' => $entrySets,
        );

        $encoder = new JsonEncoder();
        $jsonData = $encoder->encode($groupData, $format = 'json');
        $response->setContent($this->jsonpWrapper($request, $jsonData));
        $response->headers->set('Content-Type', 'application/json');


        return $response;
    }

    public function allEventsAction(Request $request)
    {
        $events = $this->getUpcomingEvents();

        $response = new Response();
        if (!$events) {
            $response->setStatusCode(404);
            return $response;
        }

        $response->setStatusCode(200);

        $responseData = array(
            'meta' => array(
                'self' => $this->generateUrl('api_all_events', array(), true),
                'mimetype' => "application/json"
            ),
            'events' => $events
        );

        $encoder = new JsonEncoder();
        $jsonData = $encoder->encode($responseData, $format = 'json');
        $response->setContent($this->jsonpWrapper($request, $jsonData));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function allGroupsAction(Request $request)
    {
        $site = $this->getCurrentSite();
        $groups = $this->getGroupManager()->getAllGroupsForSite($site);

        $response = new Response();
        if (!$groups) {
            $response->setStatusCode(404);
            return $response;
        }

        $response->setStatusCode(200);

        $groupsData = array();
        foreach ($groups as $group) {

            //only public groups
            if (!$group->getIsPublic()) {
                continue;
            }

            $data = array(
                'id' => $group->getId(),
                'name' => $group->getName(),
                'isTopic' => $group->getCategory() == Group::CAT_TOPIC,
                'isFeatured' => $group->getFeatured(),
                'url' => $this->generateUrl($group->getLinkableRouteName(), $group->getLinkableRouteParameters(), true),
            );
            $groupsData[] = $data;
        }

        $responseData = array(
            'meta' => array(
                'self' => $this->generateUrl('api_all_groups', array(), true),
                'mimetype' => "application/json"
            ),
            'groups' => $groupsData
        );

        $encoder = new JsonEncoder();
        $jsonData = $encoder->encode($responseData, 'json');
        $response->setContent($this->jsonpWrapper($request, $jsonData));
        $response->headers->set('Content-Type', 'application/json');


        return $response;
    }

    public function mobileAction(Request $request)
    {

        $response = new Response();

        $groups = $this->getGroups();
        $events = array_merge($this->getEvents(), $this->getEvents(false));
        $entrySets = $this->getEntrySets();

        if (!$groups && !$events) {
            $response->setStatusCode(404);
            return $response;
        }

        $response->setStatusCode(200);

        $responseData = array(
            'meta' => array(
                'self' => $this->generateUrl('api_mobile', array(), true),
                'mimetype' => "application/json"
            ),
            'groups' => $groups,
            'events' => $events,
            'entrySets' => $entrySets,
        );

        $encoder = new JsonEncoder();
        $jsonData = $encoder->encode($responseData, $format = 'json');
        $response->setContent($this->jsonpWrapper($request, $jsonData));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getGroups()
    {

        $groups = $this->getGroupManager()->getAllGroupsForSiteSorted($this->getCurrentSite());

        $groupsData = array();
        foreach ($groups as $group) {

            if (!$group->getIsPublic()) {
                continue;
            }

            $parentGroup = $group->getParent();
            $parentId = null;
            if ($parentGroup) {
                $parentId = $parentGroup->getId();
            }

            $childrenGroupData = array();
            foreach ($group->getChildren() as $child) {
                $childrenGroupData[] = array(
                    'id' => $child->getId(),
                    'name' => $child->getName(),
                );
            }

            $avatarPath = null;
            $avatar = $group->getGroupAvatar();
            if ($avatar) {
                $mediaResolver = $this->getMediaPathResolver();
                $avatarPath = $mediaResolver->getPath($group->getGroupAvatar(), array());
            }

            $upcomingEvents = $this->getGroupEventService()->findUpcomingEventsForGroupMostRecentFirst($group);
            $upcomingEventData = array();
            foreach ($upcomingEvents as $event) {
                $upcomingEventData[] = array(
                    'id' => $event->getId(),
                    'name' => $event->getName(),
                );
            }

            $pastEvents = $this->getGroupEventService()->findPastEventsForGroupMostRecentFirst($group, 6);
            $pastEventData = array();
            foreach ($pastEvents as $event) {
                $pastEventData[] = array(
                    'id' => $event->getId(),
                    'name' => $event->getName(),
                );
            }

            $groupsData[] = array(
                'id' => $group->getId(),
                'creator' => $group->getOwner()->getUserName(),
                'name' => $group->getName(),
                'description' => $group->getDescription(),
                'category' => $group->getCategory(),
                'memberCount' => $group->getMembers()->count(),
                'parentId' => $parentId,
                'children' => $childrenGroupData,
                'pastEvents' => $pastEventData,
                'upcomingEvents' => $upcomingEventData,
                'avatarPath' => $avatarPath,
                'entrySets' => $this->getEntrySets($group),
            );

        }

        return $groupsData;
    }


    public function getEvents($upcomingEvents = true)
    {

        if ($upcomingEvents) {
            $events = $this->getGroupEventService()->findUpcomingEventsForSiteSorted($this->getCurrentSite());
        } else {
            $events = $this->getGroupEventService()->findPastEventsForSite($this->getCurrentSite());
        }

        if (!$events) {
            return null;
        }

        $eventsData = array();
        foreach ($events as $event) {

            $sessionsData = array();
            foreach ($event->getSortedSessions() as $session) {
                $sessionsData[] = array(
                    'id' => $session->getId(),
                    'name' => $session->getName(),
                    'date' => $session->getDateString(),
                    'time' => $session->getTimeRangeString(),
                    'description' => $session->getDescription(),
                    'event' => $event->getId(),
                );
            }

            $group = $event->getGroup();
            $avatarPath = null;
            $avatar = $group->getGroupAvatar();
            if ($avatar) {
                $mediaResolver = $this->getMediaPathResolver();
                $avatarPath = $mediaResolver->getPath($avatar, array());
            }

            $data = array(
                'id' => $event->getId(),
                'creator' => $event->getUser()->getName(),
                'name' => $event->getName(),
                'description' => $event->getContent(),
                'daterange' => $event->getDateRangeString(),
                'timerange' => $event->getStartsAt()->format('g:i a') . ' - ' . $event->getEndsAt()->format('g:i a'),
                'startDateString' => $event->getStartsAt()->format('M d, Y'),
                'location' => $event->getLocation(),
                'address1' => $event->getAddress1(),
                'address2' => $event->getAddress2(),
                'group' => $group->getId(),
                'avatarPath' => $avatarPath,
                'sessions' => $sessionsData,
                'entrySets' => $this->getEntrySets($event),
                'url' => $this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
            );
            if ($upcomingEvents) {
                $data['upcoming'] = true;
            } else {
                $data['upcoming'] = false;
            }
            $eventsData[] = $data;
        }

        return $eventsData;
    }

    public function getEntrySets($parentFilter = null)
    {

        $entrySets = null;

        if ($parentFilter) {
            $entrySets = $parentFilter->getEntrySets()->toArray();
        } else {
            $entrySets = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->findAll();
        }

        $entrySets = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->sortByPopularity($entrySets);

        $entrySetsData = array();

        foreach ($entrySets as $entrySet) {

            if ($parentFilter) {
                $entrySetParent = $parentFilter;
            } else {
                $entrySetParent = $this->getIdeaService()->getParentByEntrySet($entrySet);
            }

            // We only want entrySets for groups or events on the mobile app, no site feedback lists
            if ($entrySetParent instanceof Group) {
                $parentType = 'group';
                $avatar = $entrySetParent->getGroupAvatar();
            } elseif ($entrySetParent instanceof Event) {
                $parentType = 'event';
                $avatar = $entrySetParent->getGroup()->getGroupAvatar();
            } else {
                continue;
            }

            // Don't include lists whose parent is from a different site
            if (!in_array($this->getCurrentSite(), $entrySetParent->getSites()->toArray())) {
                continue;
            }

            $avatarPath = null;
            if ($avatar) {
                $avatarPath = $this->getMediaPathResolver()->getPath($avatar, array());
            }

            $sortedEntries = $entrySet->getEntries()->toArray();
            $this->getDoctrine()->getRepository('IdeaBundle:Idea')->sortByFollows($sortedEntries);

            $entries = array();
            foreach ($sortedEntries as $entry) {

                $entryData = array(
                    'id' => $entry->getId(),
                    'name' => $entry->getName(),
                    'numVotes' => $entry->getNumVotes(),
                );

                if (!$parentFilter) {
                    $entryData['creator'] = $entry->getCreator()->getName();
                    $entryData['description'] = $entry->getDescription();
                    $entryData['tags'] = $entry->getImplodedTagString();
                }

                $entries[] = $entryData;
            }

            $entrySetData = array(
                'id' => $entrySet->getId(),
                'name' => $entrySet->getName(),
                'parentName' => $entrySetParent->getName(),
                'entries' => $entries,
                'avatarPath' => $avatarPath,
            );

            // Add these fields if we're getting all entrySets, the group and event calls only need minimal information
            if (!$parentFilter) {
                $entrySetData['description'] = $entrySet->getDescription();
                $entrySetData['parentType'] = $parentType;
                $entrySetData['parentId'] = $entrySetParent->getId();
            }

            $entrySetsData[] = $entrySetData;
        }
        return $entrySetsData;
    }

    public function jsonpWrapper(Request $request, $jsonData)
    {
        $callback = $request->query->get('callback');
        if (!$callback)
            return $jsonData;

        return $callback . "(" . $jsonData . ")";
    }

}

?>