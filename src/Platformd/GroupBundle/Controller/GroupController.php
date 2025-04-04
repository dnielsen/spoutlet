<?php

namespace Platformd\GroupBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\GroupBundle\Entity\Group;
use Platformd\GroupBundle\Entity\GroupNews;
use Platformd\GroupBundle\Entity\GroupVideo;
use Platformd\GroupBundle\Entity\GroupImage;
use Platformd\GroupBundle\Entity\GroupDiscussion;
use Platformd\GroupBundle\Entity\GroupDiscussionPost;
use Platformd\GroupBundle\Entity\GroupApplication;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Entity\GroupMassEmail;
use Platformd\IdeaBundle\Entity\Sponsor;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\Form\Type\GroupType;
use Platformd\GroupBundle\GroupEvents;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Platformd\UserBundle\Entity\RegistrationSource;
use Gedmo\Sluggable\Util\Urlizer;

/**
 * Group controller.
 *
 */
class GroupController extends Controller
{
    public function _groupMemberCheckAction($groupId)
    {
        $group = $this->getGroup($groupId);
        $user  = $this->getUser();

        $this->ensureGroupExists($group);

        $isMember    = false;
        $isOwner     = false;
        $isApplicant = false;

        if ($user){
            $isMember    = $this->getGroupManager()->isMember($user, $group);
            $isOwner     = $group->getOwner() == $user;
            $isApplicant = $this->getGroupManager()->isApplicant($user, $group);
        }

        $response = $this->render('GroupBundle:Group:_groupMemberCheck.html.twig', array(
            'isMember'    => $isMember,
            'isOwner'     => $isOwner,
            'isApplicant' => $isApplicant,
        ));

        $this->varnishCache($response, 2592000);

        return $response;
    }

    private function ensureGroupExists($group) {
        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }
    }

    private function ensureAllowed($group, $action, $forceUserLogin = true) {
        if ($forceUserLogin) {
            $this->basicSecurityCheck(array('ROLE_USER'));
        }

        $this->ensureGroupExists($group);

        $groupManager = $this->getGroupManager();

        if (!$groupManager->isAllowedTo($this->getCurrentUser(), $group, $this->getCurrentSite(), $action)) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }
    }

    private function ensureGroupExistsOnCurrentSite($group) {
        $this->ensureGroupExists($group);
        if ($group->getAllLocales()) {
            return;
        }
        foreach ($group->getSites() as $site) {
            if ($site == $this->getCurrentSite()) {
                return;
            }
        }
        throw new NotFoundHttpException('The group does not exist in this community.');
    }

    private function getYoutubeThumb($videoId) {

        if (!$videoId) {
            return false;
        }

        $url = 'http://gdata.youtube.com/feeds/api/videos/' . $videoId . '?alt=jsonc&v=2';

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $result = json_decode(curl_exec($curl), true);

        if(isset($result))
        {
            if(array_key_exists('error', $result))
            {
                return '';
            }
        } else {
            return '';
        }

        return $result['data']['thumbnail']['sqDefault'];
    }

    private function getGroupApplicationRepo() {
        return $this
            ->getEntityManager()
            ->getRepository('GroupBundle:GroupApplication');
    }

    private function sendApplicationAcceptedEmail($application) {

        $currentHost        = $this->getRequest()->getHost();
        $applicationSite    = $application->getSite();

        $baseHost           = $this->container->getParameter('base_host');

        $groupName          = $application->getGroup()->getName();
        $groupUrlRelative   = $this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($application->getGroup())));
        $groupUrlAbsolute   = sprintf('http://%s%s', $applicationSite->getFullDomain(), $groupUrlRelative);

        $subject            = "You’re approved to be in an Alienware Arena Group!";
        $message            = sprintf("Congratulations!  You’re now a member of \"%s\" (Group Page: %s).

Visit your group's page to join in on the conversation and share information with your group members.

Note: You are receiving this message because you submitted a request to join a private group.

Alienware Arena Team
", $groupName, $groupUrlAbsolute);

        $emailTo = $application->getApplicant()->getEmail();

        $this->getEmailManager()->sendEmail($emailTo, $subject, $message, "Group Application Approved", $this->getCurrentSite()->getDefaultLocale());
    }

    public function acceptApplicationAction($slug, $applicationId) {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ManageApplications');

        $appRepo = $this->getGroupApplicationRepo();

        $application = $appRepo->find($applicationId);

        if (!$application) {
            $this->setFlash('error', 'Application not found!');
            return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $user = $application->getApplicant();
        $event = $application->getEvent();

        if (!$user) {
            $this->setFlash('error', 'User not found!');
            return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $em = $this->getEntityManager();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group)) {

            $em->remove($application);
            $em->flush();

            $this->setFlash('success', 'This user is already a member of this group - application removed!');
            return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $joinAction = new GroupMembershipAction();
        $joinAction->setGroup($group);
        $joinAction->setUser($user);
        $joinAction->setAction(GroupMembershipAction::ACTION_JOINED_APPLICATION_ACCEPTED);

        $user->getPdGroups()->add($group);
        $group->getUserMembershipActions()->add($joinAction);

        $this->getUserManager()->updateUser($user);
        $groupManager->saveGroup($group);

        if (!$event) {
            $this->sendApplicationAcceptedEmail($application);
        }

        $group->getApplications()->removeElement($application);
        $em->remove($application);
        $em->flush();

        if ($event) {
            $this->getGroupEventService()->register($event, $user);

            $emailLocale = $user->getLocale() ?: 'en';
            $subject     = $this->trans('platformd.event.email.group_application_accepted_event_registered.title', array("%groupName%" => $group->getName()), 'messages', $emailLocale);
            $message     = nl2br($this->trans('platformd.event.email.group_application_accepted_event_registered.message', array(
                        '%groupUrl%' => $this->generateUrl($group->getLinkableRouteName(), $group->getLinkableRouteParameters(), true),
                        '%groupName%' => $group->getName(),
                        '%eventUrl%' => $this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
                        '%eventName%' => $event->getName(),
                        '%groupOwnerName%' => $group->getOwner()->getUsername(),
                    ), 'messages', $emailLocale));

            $emailTo = $user->getEmail();

            $this->getEmailManager()->sendHtmlEmail($emailTo, $subject, $message, "Group Application Notification", $this->getCurrentSite()->getDefaultLocale());
        }

        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('joingroup', $user->getCevoUserId());
        } catch (ApiException $e) {

        }

        $this->setFlash('success', sprintf('You have successfully accepted \'%s\' into your group!', $user->getUsername()));

        $this->varnishBan($this->generateUrl('_group_member_check', array('groupId' => $group->getId())), array('userId' => $user->getId()));

        return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
    }

    public function rejectApplicationAction($slug, $applicationId) {

        $group = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ManageApplications');

        $appRepo = $this->getGroupApplicationRepo();

        $application = $appRepo->find($applicationId);

        if (!$application) {
            $this->setFlash('error', 'Application not found!');
            return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $user = $application->getApplicant();

        if (!$user) {
            $this->setFlash('error', 'User not found!');
            return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $em = $this->getEntityManager();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group)) {

            $em->remove($application);
            $em->flush();

            $this->setFlash('error', 'This user is already a member of this group - application removed!');
            return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $em->remove($application);
        $em->flush();

        $this->setFlash('success', sprintf('You have successfully rejected \'%s\' from joining your group!', $user->getUsername()));
        $this->varnishBan($this->generateUrl('_group_member_check', array('groupId' => $group->getId())), array('userId' => $user->getId()));

        return $this->redirect($this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group))));
    }

    public function applicationsAction($slug) {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ManageApplications');

        $appRepo = $this->getGroupApplicationRepo();

        $applications = $appRepo->getApplicationsForGroup($group);

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:applications.html.twig', array(
                'applications' => $applications,
                'group' => $group,
                'permissions' => $permissions,
            ));
    }

    public function indexAction()
    {
        $this->addGroupsBreadcrumb();

        $em           = $this->getEntityManager();
        $repo         = $em->getRepository('GroupBundle:Group');
        $site         = $this->getCurrentSite();
        $exposer      = $this->container->get('media_exposer');
        $groupManager = $this->getGroupManager();

        $featuredGroups = $repo->findAllFeaturedGroupsForSite($site);
        $locationGroups = $repo->findAllGroupsWithMemberCountsByCategoryAndSite('location', $site);
        $topicGroups    = $repo->findAllGroupsWithMemberCountsByCategoryAndSite('topic', $site);
        $recentGroups   = $repo->findMostRecentlyCreatedGroupsForSite($site);
        $popularGroups  = $repo->findPopularGroupsForSite($site);

        $location = array();
        $topic    = array();

        foreach ($locationGroups as $groupInfo) {
            $group       = $groupInfo[0];
            $memberCount = $groupInfo['memberCount'];

            $data = $groupManager->getGroupIndexData($group);

            if ($data) {
                $data['memberCount'] = $memberCount;
                $location[] = $data;
            }
        }

        foreach ($topicGroups as $groupInfo) {
            $group       = $groupInfo[0];
            $memberCount = $groupInfo['memberCount'];

            $data = $groupManager->getGroupIndexData($group);

            if ($data) {
                $data['memberCount'] = $memberCount;
                $topic[] = $data;
            }
        }

//        $locationGroups = json_encode($location, JSON_HEX_APOS);
//        $topicGroups    = json_encode($topic, JSON_HEX_APOS);

        return $this->render('GroupBundle:Group:index.html.twig', array(
            'locationGroups' => $location,
            'topicGroups'    => $topic,
            'recentGroups'   => $recentGroups,
            'popularGroups'  => $popularGroups,
            'featuredGroups' => $featuredGroups,
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_GROUP),
        ));
    }

    private function getGroupPages($groups)
    {
        $groupsPerPage = 8;
        $pageCount = ceil(count($groups) / $groupsPerPage);

        $pages = array();
        $offset = 0;
        for($i = 0; $i < $pageCount; $i++)
        {
            $pages[] = array(array_slice($groups, $offset, $groupsPerPage));
            $offset += $groupsPerPage;
        }

        return $pages;
    }

    public function leaveAction($slug)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroupBySlug($slug);
        $user  = $this->getCurrentUser();

        $groupManager = $this->getGroupManager();

        if ($group->isOwner($user)) {
            $this->setFlash('error', 'You are the group owner, you are not allowed to leave the group!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        if (!$groupManager->isMember($user, $group)) {
            $this->setFlash('error', 'You are not a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $ownedEvents = $this->getGroupEventService()->findBy(array(
                'user' => $user->getId(),
                'group' => $group->getId(),
                'deleted' => 0,
                'active' => 1,
            ));

        $unexpired = 0;

        if (count($ownedEvents) > 0) {
            foreach ($ownedEvents as $event) {
                if ($event->getEndsAt() > new \DateTime()) {
                    $unexpired++;
                }
            }
        }

        if ($unexpired > 0) {
            $this->setFlash('error', 'Sorry! We are unable to process your request because you have upcoming events listed for this group. Please cancel your events by going to Account > Events or wait until your events are over.');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $this->ensureAllowed($group, 'LeaveGroup');

        $leaveAction = new GroupMembershipAction();
        $leaveAction->setGroup($group);
        $leaveAction->setUser($user);
        $leaveAction->setAction(GroupMembershipAction::ACTION_LEFT);

        $user->getPdGroups()->removeElement($group);
        //$group->getMembers()->removeElement($user);
        $group->getUserMembershipActions()->add($leaveAction);

        // TODO Add a service layer for managing groups and dispatching such events
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
        $dispatcher = $this->get('event_dispatcher');
        $event = new GroupEvent($group, $user);
        $dispatcher->dispatch(GroupEvents::GROUP_LEAVE, $event);

        $this->getUserManager()->updateUser($user);
        $this->getGroupManager()->saveGroup($group);

        $this->setFlash('success', 'You have successfully left this group!');

        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('leavegroup', $user->getCevoUserId());
        } catch (ApiException $e) {

        }

        $this->varnishBan($this->generateUrl('_group_show_content', array('slug' => $slug)));
        $this->varnishBan($this->generateUrl('_group_member_check', array('groupId' => $group->getId())), array('userId' => $user->getId()));

        return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
    }

    private function join($group, $user) {
        // TODO This should probably be refactored to use the global activity table
        $joinAction = new GroupMembershipAction();
        $joinAction->setGroup($group);
        $joinAction->setUser($user);
        $joinAction->setAction(GroupMembershipAction::ACTION_JOINED);

        $user->getPdGroups()->add($group);
        $group->getUserMembershipActions()->add($joinAction);

        // TODO Add a service layer for managing groups and dispatching such events
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
        $dispatcher = $this->get('event_dispatcher');
        $event = new GroupEvent($group, $user);
        $dispatcher->dispatch(GroupEvents::GROUP_JOIN, $event);

        $this->getUserManager()->updateUser($user);
        $this->getGroupManager()->saveGroup($group);

        if( $group->getParent() != null ) {
            $this->join($group->getParent(), $user);
        }
    }

    public function joinAction(Request $request, $slug)
    {
        // If unregistered users try to join a group, remember what group they were trying to join before bouncing to the register page
        $session = $request->getSession();
        $session->set('group_slug', $slug);
        $this->basicSecurityCheck('ROLE_USER');
        // If the user passes the security check, we don't need the slug anymore
        $session->remove('group_slug');

        $group = $this->getGroupBySlug($slug);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group) || $group->isOwner($user)) {
            $this->setFlash('success', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('slug'=>$slug)));
        }

        $this->ensureAllowed($group, 'JoinGroup');

        $this->join($group, $user);

        //$this->setFlash('success', 'You will receive an email if you are admitted into this group.');

        if ($group->getIsPublic()) {
            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('joingroup', $user->getCevoUserId());
            } catch (ApiException $e) {

            }
        }

        $groupName       = $group->getName();
        $groupUrl        = $this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group)), true);
        $groupOwnerEmail = $group->getOwner()->getEmail();

        $subject         = $this->trans('platformd.group.email.welcome.title', array('%groupName%' => $groupName));
        $messageTemplate = $this->trans('platformd.group.email.welcome.message', array(
                                        '%userName%'  => $user->getName(),
                                        '%groupName%' => $groupName,
                                        '%groupUrl%'  => $groupUrl,
                                        '%groupOwner%'=> $groupOwnerEmail,
                                        ));

        $message = nl2br($messageTemplate);

        $this->getEmailManager()->sendHtmlEmail($user->getEmail(), $subject, $message, "Group Welcome", $this->getCurrentSite()->getFullDomain());

        $this->setFlash('success', 'You have successfully joined this group!');
        $this->varnishBan($this->generateUrl('_group_show_content', array('slug' => $slug)));
        $this->varnishBan($this->generateUrl('_group_member_check', array('groupId' => $group->getId())), array('userId' => $user->getId()));

        return $this->redirect($groupUrl);
    }

    public function applyToGroupWithEventAction($slug, $eventId, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group  = $this->getGroupBySlug($slug);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        if (!$event = $this->getGroupEventService()->find($eventId)) {
            $this->setFlash('error', 'Group event not found!');
            return $this->redirect($this->generateUrl($group->getLinkableRouteName(), $grpup->getLinkableRouteParameters()));
        }

        if ($this->getGroupManager()->isMember($user, $group) || $group->isOwner($user)) {
            $this->getGroupEventService()->register($event, $user);
            $this->setFlash('success', 'Youare now attentding this event.');
            return $this->redirect($this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters()));
        }

        $userApplications = $this->getGroupApplicationRepo()->findByApplicant($user->getId());

        if ($userApplications) {
            foreach ($userApplications as $app) {

                if ($app->getGroup() && ($app->getGroup()->getId() == $group->getId())) {
                    $this->setFlash('error', 'You have already applied to this group!');
                    return $this->redirect($this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters()));
                }
            }
        }

        $this->ensureAllowed($group, 'ApplyToGroup');

        $this->getGroupManager()->autoApplyToGroup($group, $user, $event);

        $emailLocale = $group->getOwner()->getLocale() ?: 'en';
        $subject     = $this->trans('platformd.event.email.group_join_application_from_event.title', array(), 'messages', $emailLocale);
        $message     = nl2br($this->trans('platformd.event.email.group_join_application_from_event.message', array(
                    '%userName%' => $user->getUsername(),
                    '%groupUrl%' => $this->generateUrl($group->getLinkableRouteName(), $group->getLinkableRouteParameters(), true),
                    '%groupName%' => $group->getName(),
                    '%approvalUrl%' => $this->generateUrl('group_applications', array('slug' => $this->getRelevantSlugForGroup($group)), true),
                ), 'messages', $emailLocale));

        $emailTo = $group->getOwner()->getEmail();

        $this->getEmailManager()->sendHtmlEmail($emailTo, $subject, $message, "Group Application Notification", $this->getCurrentSite()->getDefaultLocale());

        $this->setFlash('success', 'A request has been sent to the group organizer to accept you into the private group. Once approved, you will also be registered for the event.');

        $this->varnishBan($this->generateUrl('_group_member_check', array('groupId' => $group->getId())), array('userId' => $user->getId()));

        return $this->redirect($this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters()));
    }

    public function applyToGroupAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group  = $this->getGroupBySlug($slug);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group) || $group->isOwner($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $userApplications = $this->getGroupApplicationRepo()->findByApplicant($user->getId());

        if ($userApplications) {
            foreach ($userApplications as $app) {

                if ($app->getGroup() && ($app->getGroup()->getId() == $group->getId())) {
                    $this->setFlash('error', 'You have already applied to this group!');
                    return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
                }
            }
        }

        $this->ensureAllowed($group, 'ApplyToGroup');

        $application = new GroupApplication();

        $form = $this->createFormBuilder($application)
            ->add('reason', 'text', array("required" => true, "max_length" => 254))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $application->setGroup($group);
                $application->setApplicant($user);
                $application->setSite($this->getCurrentSite());

                $em = $this->getEntityManager();

                $em->persist($application);
                $em->flush();

                $this->setFlash('success', 'You will receive an email if you are admitted into this group.');
                $this->varnishBan($this->generateUrl('_group_member_check', array('groupId' => $group->getId())), array('userId' => $user->getId()));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:applyToGroup.html.twig', array(
                'group' => $group,
                'form' => $form->createView(),
                'permissions' => $permissions,
            ));
    }

    public function removeAction($slug, $uid)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group  = $this->getGroupBySlug($slug);
        $user   = null;

        foreach($group->getMembers() as $member)
        {
            if($member->getId() == $uid)
            {
                $user = $member;
                break;
            }
        }

        if($user == null) {
            $this->setFlash('error', 'The user you are trying to remove could not be found!');
            return $this->redirect($this->generateUrl('group_members', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        $groupManager = $this->getGroupManager();

        if ($group->isOwner($user)) {
            $this->setFlash('error', 'You are the group organizer. Please email contact@alienwarearena.com if you want to be removed from this group.');
            return $this->redirect($this->generateUrl('group_members', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        if (!$groupManager->isMember($user, $group)) {
            $this->setFlash('error', 'You cannot remove someone who is not a member of this group!');
            return $this->redirect($this->generateUrl('group_members', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        //$this->ensureAllowed($group, 'LeaveGroup');

        $removeAction = new GroupMembershipAction();
        $removeAction->setGroup($group);
        $removeAction->setUser($user);
        $removeAction->setAction(GroupMembershipAction::ACTION_REMOVED);

        $group->getMembers()->removeElement($user);
        $group->getUserMembershipActions()->add($removeAction);

        $groupManager->saveGroup($group);

        $this->setFlash('success', sprintf('%s is no longer in this group.', $user->getUsername()));

        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('leavegroup', $user->getCevoUserId());
        } catch (ApiException $e) {

        }

        return $this->redirect($this->generateUrl('group_members', array('slug' => $this->getRelevantSlugForGroup($group))));
    }

    public function eventsAction($slug)
    {
        return $this->render('GroupBundle:Group:eventsWrapper.html.twig', array(
            'slug' => $slug,
        ));
    }

    public function _groupEventsAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $ongoingEvents  = $this->getGroupEventService()->findOngoingEventsForGroup($group);
        $upcomingEvents = $this->getGroupEventService()->findUpcomingEventsForGroupMostRecentFirst($group);
        $pastEvents     = $this->getGroupEventService()->findPastEventsForGroupMostRecentFirst($group);

        $groupEvents    = array_merge($ongoingEvents, $upcomingEvents);

        $canAdd         = $this->getGroupManager()->isAllowedTo($this->getUser(), $group, $this->getCurrentSite(), 'AddEvent');

        $response = $this->render('GroupBundle:Group:events.html.twig', array(
            'group'         => $group,
            'groupEvents'   => $groupEvents,
            'pastEvents'    => $pastEvents,
            'canAdd'        => $canAdd,
        ));

        $this->varnishCache($response, 60);

        return $response;
    }

    public function newsAction($slug)
    {
        return $this->render('GroupBundle:Group:newsWrapper.html.twig', array(
            'slug' => $slug,
        ));
    }

    public function _groupNewsAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $groupNews = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);

        $response = $this->render('GroupBundle:Group:news.html.twig', array(
            'group' => $group,
            'groupNews' => $groupNews,
            'groupManager' => $this->getGroupManager(),
        ));

        $this->varnishCache($response, 60);

        return $response;
    }

    public function addNewsAction($slug, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'AddNews');

        $tagManager = $this->getTagManager();
        $groupNews = new GroupNews();

        $form = $this->createFormBuilder($groupNews)
            ->add('title', 'text')
            ->add('article', 'purifiedTextarea', array(
                    'attr'  => array('class' => 'ckeditor')
                ))
            ->add('tags', 'text', array(
                    'label' => 'tags.forms.tags',
                    'help'  => 'tags.forms.enter_keywords_help',
                    'property_path' => false,
                    'data' => null,
                    'required' => false,
                ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupNews->setGroup($group);

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));
                $tagManager->addTags($tags, $groupNews);

                $this->getGroupManager()->saveGroupNews($groupNews);

                $tagManager->saveTagging($groupNews);

                $this->setFlash('success', 'New article posted successfully.');

                $this->varnishBan($this->generateUrl('_group_news', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#news');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:addNews.html.twig', array(
                'group' => $group,
                'newsForm' => $form->createView(),
                'newsFormAction' => $this->generateUrl('group_add_news', array('slug' => $slug)),
                'permissions' => $permissions,
            ));
    }

    public function editNewsAction($slug, $newsId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'EditNews');

        $tagManager     = $this->getTagManager();
        $newsArticle    = $this->getGroupNewsRepository()->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#news');
        }

        $tagManager->loadTagging($newsArticle);

        $form = $this->createFormBuilder($newsArticle)
            ->add('title', 'text')
            ->add('article', 'purifiedTextarea', array(
                    'attr'  => array('class' => 'ckeditor')
                ))
            ->add('tags', 'text', array(
                    'label' => 'tags.forms.tags',
                    'help'  => 'tags.forms.enter_keywords_help',
                    'property_path' => false,
                    'data' => $tagManager->getConcatenatedTagNames($newsArticle),
                    'required' => false,
                ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $newsArticle->setGroup($group);

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));

                $tagManager->replaceTags($tags, $newsArticle);
                $this->getGroupManager()->saveGroupNews($newsArticle);
                $tagManager->saveTagging($newsArticle);

                $this->setFlash('success', 'New article updated successfully.');
                $this->varnishBan($this->generateUrl('_group_news', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#news');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:editNews.html.twig', array(
                'group' => $group,
                'newsForm' => $form->createView(),
                'newsFormAction' => $this->generateUrl('group_edit_news', array('slug' => $slug, 'newsId' => $newsId)),
                'permissions' => $permissions,
            ));
    }

    public function deleteNewsAction($slug, $newsId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'DeleteNews');

        $newsArticle = $this->getGroupNewsRepository()->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#news');
        }

        $newsArticle->setDeleted(true);

        $this->getGroupManager()->saveGroupNews($newsArticle);

        $this->setFlash('success', 'News article was deleted successfully!');

        $this->varnishBan($this->generateUrl('_group_news', array('slug' => $slug)));

        return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#news');
    }

    public function imageAction($slug)
    {
        return $this->render('GroupBundle:Group:imagesWrapper.html.twig', array(
            'slug' => $slug,
        ));
    }

    public function _groupImagesAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ViewGroupContent', false);
        $site = $this->getCurrentSite();
        $features = $site->getSiteFeatures();

        $groupImage = $site->getSiteFeatures()->getHasPhotos() ? $this->getGalleryMediaRepository()->findImagesForGroup($group) : $this->getGroupImageRepository()->getImagesForGroupMostRecentFirst($group);

        // 16 images per page
        $itemsPerPage = 16;
        $totalPageCount = ceil(count($groupImage) / $itemsPerPage);

        $pages = array();
        $offset = 0;
        for($i = 0; $i < $totalPageCount; $i++)
        {
            $pages[] = array(array_slice($groupImage, $offset, $itemsPerPage));
            $offset += $itemsPerPage;
        }

        $response = $this->render('GroupBundle:Group:images.html.twig', array(
            'pages'        => $pages,
            'group'        => $group,
            'groupManager' => $this->getGroupManager(),
        ));

        $this->varnishCache($response, 60);

        return $response;
    }


    public function showImageAction ($id)
    {
        $groupImageRepo = $this->getGroupImageRepository();
        $groupImage     = $groupImageRepo->find($id);

        if ($groupImage) {
            $group = $groupImage->getGroup();

            $this->ensureAllowed($group, 'ViewGroupContent', false);

            $otherMedia     = $groupImageRepo->findAllGroupImagesNewestFirstExcept($group, $id);

            $otherMediaPerPage = 3;
            $pageCount = ceil(count($otherMedia) / $otherMediaPerPage);

            $otherMediaPages = array();
            $offset = 0;
            for($i = 0; $i < $pageCount; $i++)
            {
                $otherMediaPages[] = array_slice($otherMedia, $offset, $otherMediaPerPage);
                $offset += $otherMediaPerPage;
            }

            $galleryMedia   = $this->getEntityManager()->getRepository('SpoutletBundle:GalleryMedia')->findOneByImage($groupImage->getImage()->getId());

            $permalink = $this->get('platformd.model.comment_manager')->checkThread($groupImage);

            return $this->render('GroupBundle:Group:showImage.html.twig', array(
                    'media'             => $groupImage,
                    'group'             => $group,
                    'otherMediaPages'   => $otherMediaPages,
                    'galleryMediaItem'  => $galleryMedia,
                    'groupManager'      => $this->getGroupManager(),
                    'permalink'         => $permalink,
                ));
        }

        $this->setFlash('error', 'Group image not found!');
        return $this->redirect($this->generateUrl('groups'));
    }

    public function addImageAction($slug, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'AddImage');

        $groupImage = new GroupImage();

        $form = $this->createFormBuilder($groupImage)
            ->add('title', 'text')
            ->add('image', new MediaType(), array('image_label' => 'Image'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupImage->setGroup($group);

                $this->getGroupManager()->saveGroupImage($groupImage);

                try {
                    $response = $this->getCEVOApiManager()->GiveUserXp('addcontent', $this->getCurrentUser()->getCevoUserId());
                } catch (ApiException $e) {

                }

                $this->setFlash('success', 'Image posted successfully.');

                $this->varnishBan($this->generateUrl('_group_images', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#images');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:addImage.html.twig', array(
                'group' => $group,
                'imageForm' => $form->createView(),
                'imageFormAction' => $this->generateUrl('group_add_image', array('slug' => $slug)),
                'permissions' => $permissions,
            ));
    }

    public function editImageAction($slug, $imageId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'EditImage');

        $image = $this->getGroupImageRepository()->find($imageId);

        if (!$image) {
            $this->setFlash('error', 'Image does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#images');
        }

        $form = $this->createFormBuilder($image)
            ->add('title', 'text')
            ->add('image', new MediaType(), array('image_label' => 'Image'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $image->setGroup($group);

                $this->getGroupManager()->saveGroupImage($image);

                $this->setFlash('success', 'Image updated successfully.');
                $this->varnishBan($this->generateUrl('_group_images', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#images');
            }
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:editImage.html.twig', array(
                'group' => $group,
                'imageForm' => $form->createView(),
                'imageFormAction' => $this->generateUrl('group_edit_image', array('slug' => $slug, 'imageId' => $imageId)),
                'permissions' => $permissions,
            ));
    }

    public function deleteImageAction($slug, $imageId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'DeleteImage');

        $em    = $this->getEntityManager();
        $image = $this->getGroupImageRepository()->find($imageId);

        if (!$image) {
            $this->setFlash('error', 'Image does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#images');
        }

        $image->setDeleted(true);

        $em->persist($image);
        $em->flush();

        $this->setFlash('success', 'Image was deleted successfully!');

        $this->varnishBan($this->generateUrl('_group_images', array('slug' => $slug)));

        return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#images');
    }

    public function videosAction($slug)
    {
        return $this->render('GroupBundle:Group:videosWrapper.html.twig', array(
            'slug' => $slug,
        ));
    }

    public function _groupVideosAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);

        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $hasVideoFeature = $this->getCurrentSite()->getSiteFeatures()->getHasVideo();

        $groupVideos = $hasVideoFeature ? $this->getYoutubeManager()->findVideosForGroup($group) : $groupVideos = $this->getGroupVideoRepository()->getVideosForGroupMostRecentFirst($group);

        // 3 images per page
        $itemsPerPage = 3;
        $totalPageCount = ceil(count($groupVideos) / $itemsPerPage);

        $pages = array();
        $offset = 0;
        for($i = 0; $i < $totalPageCount; $i++)
        {
            $pages[] = array(array_slice($groupVideos, $offset, $itemsPerPage));
            $offset += $itemsPerPage;
        }

        $response = $this->render('GroupBundle:Group:videos.html.twig', array(
            'pages' => $pages,
            'group' => $group,
            'groupManager' => $this->getGroupManager(),
        ));

        $this->varnishCache($response, 60);

        return $response;
    }

    public function addVideoAction($slug, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'AddVideo');

        $groupVideo = new GroupVideo();

        $form = $this->createFormBuilder($groupVideo)
            ->add('title', 'text', array('label' => 'Video Name'))
            ->add('youTubeVideoId', 'text', array('label' => 'YouTube ID', 'help'  => 'Paste Youtube ID (e.g. UN6ZT9lDIOk if the URL is something like http://www.youtube.com/watch?v=UN6ZT9lDIOk&feature=fvst'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupVideo->setGroup($group);

                $groupVideo->setYouTubeThumb($this->getYoutubeThumb($groupVideo->getYouTubeVideoId()));

                $this->getGroupManager()->saveGroupVideo($groupVideo);

                $api = $this->getCEVOApiManager();

                try {
                    $response = $api->GiveUserXp('addcontent', $this->getCurrentUser()->getCevoUserId());
                } catch (ApiException $e) {

                }

                $this->setFlash('success', 'New video posted successfully.');

                $this->varnishBan($this->generateUrl('_group_videos', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#videos');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:addVideo.html.twig', array(
                'group' => $group,
                'videoForm' => $form->createView(),
                'videoFormAction' => $this->generateUrl('group_add_video', array('slug' => $slug)),
                'permissions' => $permissions,
            ));
    }

    public function editVideoAction($slug, $videoId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'EditVideo');

        $video = $this->getGroupVideoRepository()->find($videoId);

        if (!$video) {
            $this->setFlash('error', 'Video does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#videos');
        }

        $form = $this->createFormBuilder($video)
            ->add('title', 'text', array('label' => 'Video Name'))
            ->add('youTubeVideoId', 'text', array('label' => 'YouTube ID', 'help'  => 'Paste Youtube ID (e.g. UN6ZT9lDIOk if the URL is something like http://www.youtube.com/watch?v=UN6ZT9lDIOk&feature=fvst'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $video->setGroup($group);

                $video->setYouTubeThumb($this->getYoutubeThumb($video->getYouTubeVideoId()));

                $this->getGroupManager()->saveGroupVideo($video);

                $this->setFlash('success', 'Video updated successfully.');

                $this->varnishBan($this->generateUrl('_group_videos', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#videos');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:editVideo.html.twig', array(
                'group' => $group,
                'videoForm' => $form->createView(),
                'videoFormAction' => $this->generateUrl('group_edit_video', array('slug' => $slug, 'videoId' => $videoId)),
                'permissions' => $permissions,
            ));
    }

    public function deleteVideoAction($slug, $videoId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'DeleteVideo');

        $videoArticle = $this->getGroupVideoRepository()->find($videoId);

        if (!$videoArticle) {
            $this->setFlash('error', 'Video does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#videos');
        }

        $em = $this->getEntityManager();
        $em->remove($videoArticle);
        $em->flush();

        $this->setFlash('success', 'Video was deleted successfully!');

        $this->varnishBan($this->generateUrl('_group_videos', array('slug' => $slug)));

        return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#videos');
    }

    public function discussionsAction($slug)
    {
        return $this->render('GroupBundle:Group:discussionsWrapper.html.twig', array(
            'slug' => $slug,
        ));
    }

    public function _groupDiscussionsAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);

        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $request = $this->getRequest();
        $page = $request->query->get('page', 1);

        $pager = $this->getGroupDiscussionRepository()->getDiscussionsForGroupMostRecentFirst($group, 11, $page);
        $results = $pager->getCurrentPageResults();

        $response = $this->render('GroupBundle:Group:discussions.html.twig', array(
            'group'        => $group,
            'results'      => $results,
            'pager'        => $pager,
            'groupManager' => $this->getGroupManager(),
        ));

        $this->varnishCache($response, 60);

        return $response;
    }

    public function enableDiscussionsAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);

        $this->ensureAllowed($group, 'ManageDiscussions');

        $group->setDiscussionsEnabled(true);
        $this->getGroupManager()->saveGroup($group);

        return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
    }

    public function disableDiscussionsAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);

        $this->ensureAllowed($group, 'ManageDiscussions');

        $group->setDiscussionsEnabled(false);
        $this->getGroupManager()->saveGroup($group);

        return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
    }

    public function addDiscussionAction($slug, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'AddDiscussion');

        $tagManager      = $this->getTagManager();
        $groupDiscussion = new GroupDiscussion();


        $form = $this->createFormBuilder($groupDiscussion)
            ->add('title', 'text', array('label' => 'Discussion Name'))
            ->add('content', 'purifiedTextarea', array(
                    'label' => 'Content',
                    'help'  => 'Give a description for your discussion.',
                    'required' => true,
                    'attr'  => array('class' => 'ckeditor'),
                ))
            ->add('tags', 'text', array(
                    'label' => 'tags.forms.tags',
                    'help'  => 'tags.forms.enter_keywords_help',
                    'property_path' => false,
                    'data' => null,
                ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupDiscussion->setGroup($group);

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));
                $tagManager->addTags($tags, $groupDiscussion);

                $this->getGroupManager()->createGroupDiscussion($groupDiscussion);

                $tagManager->saveTagging($groupDiscussion);

                $this->setFlash('success', 'New discussion posted successfully.');

                $this->varnishBan($this->generateUrl('_group_discussions', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:addDiscussion.html.twig', array(
                'group' => $group,
                'discussionForm' => $form->createView(),
                'discussionFormAction' => $this->generateUrl('group_add_discussion', array('slug' => $slug)),
                'permissions' => $permissions,
            ));
    }

    public function editDiscussionAction($slug, $discussionId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'EditDiscussion');

        $tagManager = $this->getTagManager();
        $discussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$discussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
        }

        $tagManager->loadTagging($discussion);

        $form = $this->createFormBuilder($discussion)
            ->add('title', 'text', array('label' => 'Discussion Name'))
            ->add('content', 'purifiedTextarea', array(
                    'label' => 'Content',
                    'help'  => 'Give a description for your discussion.',
                    'attr'  => array('class' => 'ckeditor'),
                ))
            ->add('tags', 'text', array(
                    'label' => 'tags.forms.tags',
                    'help'  => 'tags.forms.enter_keywords_help',
                    'property_path' => false,
                    'data' => $tagManager->getConcatenatedTagNames($discussion),
                ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $discussion->setGroup($group);

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));
                $tagManager->replaceTags($tags, $discussion);

                $this->getGroupManager()->updateGroupDiscussion($discussion);

                $tagManager->saveTagging($discussion);

                $this->setFlash('success', 'Discussion updated successfully.');

                $this->varnishBan($this->generateUrl('_group_discussions', array('slug' => $slug)));

                return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:editDiscussion.html.twig', array(
                'group' => $group,
                'discussionForm' => $form->createView(),
                'discussionFormAction' => $this->generateUrl('group_edit_discussion', array('slug' => $slug, 'discussionId' => $discussionId)),
                'discussionId' => $discussionId,
                'permissions' => $permissions,
            )
        );
    }

    public function deleteDiscussionAction($slug, $discussionId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'DeleteDiscussion');

        $discussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$discussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
        }

        $this->getGroupManager()->deleteGroupDiscussion($discussion);

        $this->setFlash('success', 'Discussion was deleted successfully!');

        $this->varnishBan($this->generateUrl('_group_discussions', array('slug' => $slug)));

        return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
    }

    public function viewDiscussionAction($slug, $discussionId, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ViewDiscussion', false);

        $groupDiscussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$groupDiscussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
        }

        // We increment viewCount
        $this->getGroupManager()->viewGroupDiscussion($groupDiscussion, $request->getSession(), $this->getCurrentUser());

        $page = $request->query->get('page', 1);

        $pager = $this->getGroupDiscussionPostRepository()->getDiscussionPostsMostRecentLast($groupDiscussion, 10, $page);
        $groupDiscussionPosts = $pager->getCurrentPageResults();


        $groupDiscussionPost = new GroupDiscussionPost();
        $form = $this->createFormBuilder($groupDiscussionPost)
            ->add('content', 'purifiedTextarea', array(
                    'label' => 'Message',
                    'attr'  => array('class' => 'ckeditor'),
                ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupDiscussionPost->setGroupDiscussion($groupDiscussion);

                $this->getGroupManager()->saveGroupDiscussionPost($groupDiscussionPost);

                $this->setFlash('success', 'Your reply posted successfully.');

                return $this->redirect($this->generateUrl('group_view_discussion', array(
                            'slug' => $slug,
                            'discussionId' => $discussionId
                        )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:viewDiscussion.html.twig', array(
                'discussion' => $groupDiscussion,
                'discussionPosts' => $groupDiscussionPosts,
                'group' => $group,
                'pager' => $pager,
                'replyForm' => $form->createView(),
                'replyFormAction' => $this->generateUrl('group_view_discussion', array(
                        'slug' => $slug,
                        'discussionId' => $discussionId
                    )),
                'groupManager' => $this->getGroupManager(),
                'permissions' => $permissions,
            ));
    }

    public function replyDiscussionAction($slug, $discussionId, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'ViewDiscussion');

        $groupDiscussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$groupDiscussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))) . '#discussions');
        }

        $groupDiscussionPost = new GroupDiscussionPost();

        $form = $this->createFormBuilder($groupDiscussionPost)
            ->add('content', 'textarea', array('label' => 'Message'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupDiscussionPost->setGroupDiscussion($groupDiscussion);

                $this->getGroupManager()->saveGroupDiscussionPost($groupDiscussionPost);

                $this->setFlash('success', 'Your reply posted successfully.');

                return $this->redirect($this->generateUrl('group_view_discussion', array(
                            'slug' => $slug,
                            'discussionId' => $discussionId
                        )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:replyDiscussion.html.twig', array(
                'group' => $group,
                'discussion' => $groupDiscussion,
                'replyForm' => $form->createView(),
                'replyFormAction' => $this->generateUrl('group_reply_discussion', array(
                        'slug' => $slug,
                        'discussionId' => $discussionId
                    )),
                'groupManager' => $this->getGroupManager(),
                'permissions' => $permissions,
            ));
    }

    public function editDiscussionPostAction($id, Request $request)
    {
        $groupDiscussionPost = $this->getGroupDiscussionPostRepository()->find($id);

        if (!$groupDiscussionPost) {
            throw new NotFoundHttpException('Discussion Post does not exist!');
        }

        $groupDiscussion    = $groupDiscussionPost->getGroupDiscussion();
        $group              = $groupDiscussion->getGroup();

        // If user is not post owner, then we check if they have group admin rights
        if ($this->getCurrentUser() !== $groupDiscussionPost->getAuthor()) {
            $this->ensureAllowed($group, 'EditDiscussion');
        }

        $form = $this->createFormBuilder($groupDiscussionPost)
            ->add('content', 'purifiedTextarea', array(
                    'label' => 'Message',
                    'attr'  => array('class' => 'ckeditor'),
                ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $this->getGroupManager()->saveGroupDiscussionPost($groupDiscussionPost);

                $this->setFlash('success', 'You edited the reply successfully.');

                return $this->redirect($this->generateUrl('group_view_discussion', array(
                            'slug' => $this->getRelevantSlugForGroup($group),
                            'discussionId' => $groupDiscussion->getId()
                        )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:replyDiscussion.html.twig', array(
                'group' => $group,
                'groupManager' => $this->getGroupManager(),
                'discussion' => $groupDiscussion,
                'replyForm' => $form->createView(),
                'replyFormAction' => $this->generateUrl('group_edit_discussion_post', array(
                        'id' => $id,
                        'discussionId' => $groupDiscussion->getId()
                    )),
                'permissions' => $permissions,
            ));
    }

    public function deleteDiscussionPostAction($id, Request $request)
    {
        $groupDiscussionPost = $this->getGroupDiscussionPostRepository()->find($id);

        if (!$groupDiscussionPost) {
            throw new NotFoundHttpException('Discussion Post does not exist!');
        }

        $groupDiscussion    = $groupDiscussionPost->getGroupDiscussion();
        $group              = $groupDiscussion->getGroup();

        // If user is not post owner, then we check if they have group admin rights
        if ($this->getCurrentUser() !== $groupDiscussionPost->getAuthor()) {
            $this->ensureAllowed($group, 'DeleteDiscussion');
        }

        $this->getGroupManager()->deleteGroupDiscussionPost($groupDiscussionPost);

        $this->setFlash('success', 'Discussion Post was deleted successfully!');

        return $this->redirect($this->generateUrl('group_view_discussion', array(
                    'slug' => $this->getRelevantSlugForGroup($group),
                    'discussionId' => $groupDiscussion->getId()
                )));
    }

    public function aboutAction($slug)
    {
        return $this->render('GroupBundle:Group:aboutWrapper.html.twig', array(
            'slug' => $slug,
        ));
    }

    public function _groupAboutAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);

        $response = $this->render('GroupBundle:Group:about.html.twig', array(
            'group' => $group,
        ));

        $this->varnishCache($response, 86400);

        return $response;
    }

    public function showAction($slug)
    {
        $group = $this->getGroupBySlug($slug);
        $this->ensureGroupExistsOnCurrentSite($group);

        $upcomingEvents = $this->getGroupEventService()->findUpcomingEventsForGroupMostRecentFirst($group);
        $pastEvents     = $this->getGroupEventService()->findPastEventsForGroupMostRecentFirst($group);

        $locationGroups = array();
        $locationGroups = $this->collectLocations($group, $locationGroups);

        return $this->render('GroupBundle:Group:show.html.twig', array(
            'group'          => $group,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents'     => $pastEvents,
            'locationGroups' => $locationGroups,
        ));
    }

    private function collectLocations($group) {

        $locationGroups = array();
        foreach($group->getChildren() as $child) {
            if ($child->getCategory() == Group::CAT_LOCATION) {
                $data = $this->getGroupManager()->getGroupIndexData($child);
                if ($data) {
                    $locationGroups[] = $data;
                }
            }
        }
        return $locationGroups;
    }

    public function _groupShowContentAction($slug)
    {
        $this->addGroupsBreadcrumb();

        $group  = $this->getGroupBySlug($slug);

        if ($group && $group->getDeleted()) {
            $this->setFlash('error', 'Sorry, this group does not exist.');
            return $this->redirect($this->generateUrl('groups'));
        }

        $commentTotal   = $this->getTotalCommentCountForGroup('group-'.$group->getId());
        $upcomingEvents = $this->getGroupEventService()->findUpcomingEventsForGroupMostRecentFirst($group, 5);
        $pastEvents     = $this->getGroupEventService()->findPastEventsForGroupMostRecentFirst($group, 5);
        $memberCount    = $this->getGroupManager()->getMembershipCountByGroup($group);

        $contest = $this->getContestRepository()->findContestByGroup($group);

        $contestMemberCount = 0;
        $isEntered = false;

        if($contest != null) {
            $contestMemberCount = $this->getMemberActionRepository()->getMembersJoinedCountByGroup($group, $contest->getVotingStart(), $contest->getVotingEnd());
            $isEntered = $contest->getVotingEnd() > new \DateTime('now');
        }

        $permalink   = $this->get('platformd.model.comment_manager')->checkThread($group);

        $response = $this->render('GroupBundle:Group:_showContent.html.twig', array(
            'commentTotal'   => $commentTotal,
            'group'          => $group,
            'isEntered'      => $isEntered,
            'contestCount'   => $contestMemberCount,
            'contest'        => $contest,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents'     => $pastEvents,
            'memberCount'    => $memberCount,
            'permalink'      => $permalink,
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_GROUP, 'id'=>$group->getId()),
        ));

        $this->varnishCache($response, 60);

        return $response;
    }

    public function showPrefixedAction($slug)
    {
        $this->setFlash('info', 'Group URLs have changed - please update your bookmarks!');

        $url = $this->generateUrl('group_show', array('slug' => $slug));
        return $this->redirect($url);
    }

    public function newGettingStartedAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        return $this->render('GroupBundle:Group:newGettingStarted.html.twig');
    }

    public function newAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $this->addGroupsBreadcrumb()->addChild('New Group');

        $tagManager = $this->getTagManager();
        $group      = new Group();

        // assume that the group is only being created because the user wants to do something that requires a group first and they have none.
        if ($then = $request->query->get('then')) {
            $request->getSession()->set(
                'PostCreateAction',
                $then
            );

            $group->setDescription('Welcome to my group! <br /><br />This is the place to share your thoughts with like-minded folks on this topic. <br /><br />Feel free to start a discussion on this topic.');
        }

        // Coming from 'Create Sub Group' link
        if ($parentId = $request->query->get('parentGroupId')) {
            $parentGroup = $this->getGroup($parentId);
        // Creating group on community site
        } else {
            $parentGroup = $this->getCurrentSite()->getCommunityGroup();
        }

        if ($parentGroup) {
            $group->setParent($parentGroup);

            if ($parentGroup->getCategory() == Group::CAT_TOPIC) {
                $group->setCategory(Group::CAT_LOCATION);
            } elseif ($parentGroup->getCategory() == Group::CAT_COMPANY) {
                $group->setCategory(Group::CAT_DEPARTMENT);
            }
        }

        $form = $this->createForm(new GroupType($this->getUser(), $group, $tagManager, false, $this->getCurrentSite()), $group);

        if($previous = $this->getReturnUrl($request)) {
            $request->getSession()->set(
                'ContestReturnUrl',
                $previous
            );
        }

        if ($this->processForm($form, $request)) {

            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('addcontent', $this->getCurrentUser()->getCevoUserId());
            } catch(ApiException $e) {
            }

            if($then = $request->getSession()->get('PostCreateAction')) {

                if ($then == "group_event") {

                    $this->setFlash('success', 'Your group was created. Fill in the details below to list your upcoming event.');

                    $url = $this->generateUrl('group_event_new', array('groupSlug' => $this->getRelevantSlugForGroup($group)));
                    $request->getSession()->remove('PostCreateAction');
                    return $this->redirect($url);
                }
                elseif ($then == "campsite_event") {

                    $this->setFlash('success', 'Your group was created. Fill in the details below to list your upcoming event.');

                    $url = $this->generateUrl('idea_admin_event', array('groupSlug' => $this->getRelevantSlugForGroup($group), 'eventId' => 'newEvent'));
                    $request->getSession()->remove('PostCreateAction');
                    return $this->redirect($url);
                }
            }

            $this->setFlash('success', 'The group was created!');

            if($return = $request->getSession()->get('ContestReturnUrl')) {
                $request->getSession()->remove('ContestReturnUrl');
                return $this->redirect($return);
            }

            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        return $this->render('GroupBundle:Group:new.html.twig', array(
                'entity' => $group,
                'form'   => $form->createView()
            ));
    }

    public function editAction($slug, Request $request)
    {
        $group  = $this->getGroupBySlug($slug);

        // Save the group's old children when requesting the edit form, we'll get it when processing the form
        if ($request->getMethod() == 'GET') {
            $oldChildren = array();
            foreach ($group->getChildren() as $child) {
                $oldChildren[] = $child->getId();
            }
            $request->getSession()->set('oldChildrenIds', $oldChildren);
        }
        $this->ensureAllowed($group, 'EditGroup');

        $this->addGroupsBreadcrumb()->addChild('Edit Group');
        $tagManager = $this->getTagManager();
        $tagManager->loadTagging($group);
        
        $editForm = $this->createForm(new GroupType($this->getUser(), $group, $tagManager, false, $this->getCurrentSite()), $group);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The group was saved!');

            $this->varnishBan($this->generateUrl('_group_about', array('slug' => $slug)));

            return $this->redirect($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
        }

        return $this->render('GroupBundle:Group:edit.html.twig', array(
                'group'       => $group,
                'edit_form'   => $editForm->createView(),
            ));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
            ;
    }

    public function deleteAction($slug)
    {
        $group  = $this->getGroupBySlug($slug);
        $this->ensureAllowed($group, 'DeleteGroup');

        $group->setDeleted(true);

        $this->getGroupManager()->saveGroup($group);

        $this->setFlash('success', 'The group was successfully deleted!');

        return $this->redirect($this->generateUrl('groups'));
    }

    public function membersAction($slug) {
        $group       = $this->getGroupBySlug($slug);
        $currentUser = $this->getCurrentUser();
        $canRemove   = $group->isOwner($currentUser) && $currentUser !== null;
        $repo        = $this->getEntityManager()->getRepository('GroupBundle:Group');
        $request     = $this->getRequest();
        $page        = $request->query->get('page', 1);

        $adapter  = new ArrayAdapter($repo->getGroupMembers($group->getId()));
        $pager    = new Pagerfanta($adapter);

        $pager->setMaxPerPage(50);

        $page = $page > $pager->getNbPages() ? $pager->getNbPages() : $page;

        $pager->setCurrentPage($page);

        $members  = $pager->getCurrentPageResults();

        if($currentUser !== null) {
            $canRemove = $currentUser->getAdminLevel() == 'ROLE_SUPER_ADMIN';
        }

        $permissions = $this->getGroupManager()->getPermissions($this->getUser(), $group, $this->getCurrentSite());

        return $this->render('GroupBundle:Group:members.html.twig', array(
                'group'       => $group,
                'canRemove'   => $canRemove,
                'members'     => $members,
                'pager'       => $pager,
                'permissions' => $permissions,
            ));
    }

    public function contactAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroupBySlug($slug);

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }

        $this->ensureAllowed($group, 'ContactGroup');

        $user          = $this->getCurrentUser();
        $hitEmailLimit = $this->getDoctrine()->getEntityManager()->getRepository('GroupBundle:GroupMassEmail')->hasUserHitEmailLimitForGroup($user, $group);

        if ($hitEmailLimit) {
            $this->setFlash('error', 'platformd.groups.contact.error.email_limit_hit');
            return $this->redirect($this->generateUrl('group_show', array(
                'slug' => $slug
            )));
        }

        $email       = new GroupMassEmail($group);
        $emailLocale = $this->getLocale() ?: 'en';

        $email->setSubject($this->trans(
            'platformd.groups.contact.title',
            array('%groupName%' => $group->getName()),
            'messages',
            $emailLocale
        ));

        $form = $this->createFormBuilder($email)
            ->add('message', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor'),
                'label' => 'platformd.groups.contact.form.message',
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {

            $form->bindRequest($request);

            if ($form->isValid()) {

                $recipientCount = $this->getGroupManager()->getMembershipCountByGroup($group);

                if ($recipientCount < 1) {
                    $this->setFlash('error', 'platformd.groups.contact.error.no_recipients');
                    return $this->render('GroupBundle:Group:contact.html.twig', array(
                        'group' => $group,
                        'form'  => $form->createView(),
                    ));
                }

                $email = $form->getData();

                $email->setSender($user);
                $email->setSite($this->getCurrentSite());
                $email->setSentToAll(true);

                $content = $email->getMessage();

                $email->setMessage($this->trans(
                    'platformd.groups.contact.message',
                    array(
                        '%content%' => $content,
                        '%username%' => $user->getUsername(),
                        '%groupUrl%' => $this->generateUrl('group_show', array('slug' => $slug), true),
                        '%groupName%' => $group->getName(),
                    ),
                    'messages',
                    $emailLocale
                ));

                $emailManager = $this->container->get('platformd.model.email_manager');
                $queueResult  = $emailManager->queueMassEmail($email);

                $this->setFlash('success', $this->transChoice(
                    'platformd.groups.contact.confirmation',
                    $recipientCount,
                    array('%recipientCount%' => $recipientCount)
                ));

                return $this->redirect($this->generateUrl('group_show', array(
                    'slug' => $slug
                )));
            }

            $this->setFlash('error', 'platformd.groups.contact.error.generic_error');
        }

        return $this->render('GroupBundle:Group:contact.html.twig', array(
            'group' => $group,
            'form'  => $form->createView(),
        ));
    }

    public function emailPreviewAction($slug, Request $request)
    {
        $group = $this->getGroupBySlug($slug);
        $email = new GroupMassEmail($group);
        $user  = $this->getCurrentUser();

        $emailLocale = $this->getLocale() ?: 'en';

        $email->setSubject($this->trans(
            'platformd.groups.contact.title',
            array('%groupName%' => $group->getName()),
            'messages',
            $emailLocale
        ));

        $form = $this->createFormBuilder($email)
            ->add('message', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor'),
                'label' => 'platformd.groups.contact.form.message',
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $email = $form->getData();

                $content = $email->getMessage();

                $email->setMessage($this->trans(
                    'platformd.groups.contact.message',
                    array(
                        '%content%' => $content,
                        '%username%' => $user->getUsername(),
                        '%groupUrl%' => $this->generateUrl('group_show', array('slug' => $slug), true),
                        '%groupName%' => $group->getName(),
                    ),
                    'messages',
                    $emailLocale
                ));

                return $this->render('EventBundle::contactPreview.html.twig', array(
                    'subject' => $email->getSubject(),
                    'message' => $email->getMessage(),
                ));
            }
        }

        $this->setFlash('error', 'platformd.groups.contact.error.preview_error');

        return $this->redirect($this->generateUrl('group_contact', array(
            'slug' => $slug,
        )));
    }

    private function processForm(Form $form, Request $request)
    {
        $tagManager = $this->getTagManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $group = $form->getData();

                $slug = Urlizer::urlize($this->getRelevantSlugForGroup($group));

                // If the relative slug wasn't specified, use the global slug
                if (!$relativeSlug = $group->getRelativeSlug()) {
                    $relativeSlug = $slug;
                } else {
                    $relativeSlug = Urlizer::urlize($relativeSlug);
                }

                // Check for a conflicting relative slug on the same site
                if ($conflictingGroup = $this->getDoctrine()->getRepository('GroupBundle:Group')->findGroupByRelativeSlugAndSite($relativeSlug, $this->getCurrentSite())) {
                    if ($conflictingGroup->getId() != $group->getId()) {
                        $this->setFlash('error', 'A group with this slug already exists in this community. Please choose another.');
                        return false;
                    }
                }

                $group->setSlug($slug);
                $group->setRelativeSlug($relativeSlug);

                // Clear out the group's old children before adding the new ones
                if ($oldChildrenIds = $request->getSession()->get('oldChildrenIds')) {
                    foreach ($oldChildrenIds as $childId) {
                        $this->getGroup($childId)->setParent(null);
                    }
                    $request->getSession()->remove('oldChildrenIds');
                }

                foreach ($group->getChildren() as $child) {
                    $child->setParent($group);
                }

                $userIsAdmin  = $this->getUser()->hasRole('ROLE_SUPER_ADMIN');
                $noSitePicked = !$group->getAllLocales() && $group->getSites()->count() < 1;

                if (!$userIsAdmin || $noSitePicked) {
                    $group->setAllLocales(false);
                    $group->getSites()->clear();
                    $group->getSites()->add($this->getCurrentSite());
                }

                $deleted = $group->getDeleted();

                if (!$userIsAdmin && $deleted) {
                    return false;
                }

                if ($deleted) {
                    $group->setDeletedReason(GROUP::DELETED_BY_ADMIN);
                }

                if ($group->getFeatured()) {
                    $group->setFeaturedAt(new \DateTime('now'));
                }

                if ($group->getCategory() == Group::CAT_DEPARTMENT) {
                    $sponsor = new Sponsor();
                    $sponsor->setName($group->getName());
                    $sponsor->setCreator($this->getCurrentUser());
                    $sponsor->setDepartment($group);
                    $sponsor->setUrl($group->getExternalUrl());
                    $sponsor->setImage($group->getGroupAvatar());
                    $group->setSponsor($sponsor);
                }

                $this->getGroupManager()->saveGroup($group);

                if (!$group->getEntrySetRegistration())
                {
                    $esReg = $group->createEntrySetRegistration();
                    $em = $this->getDoctrine()->getEntityManager();
                    $em->persist($esReg);
                    $em->flush();
                }

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));
                $group->getId() ? $tagManager->replaceTags($tags, $group) : $tagManager->addTags($tags, $group);

                $tagManager->saveTagging($group);

                return true;
            }
        }

        return false;
    }

    private function getTotalCommentCountForGroup($groupId) {
        return $this->getEntityManager()
            ->getRepository('SpoutletBundle:Thread')
            ->getTotalCommentsByThreadId($groupId);
    }

    private function addGroupsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Groups', array(
                'route' => 'groups'
            ));

        return $this->getBreadcrumbs();
    }

    private function getEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }

    private function getGalleryMediaRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:GalleryMedia');
    }

    private function getGroupImageRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:GroupImage');
    }

    private function getGroupNewsRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:GroupNews');
    }

    private function getGroupVideoRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:GroupVideo');
    }

    private function getContestRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Contest');
    }

    private function getMemberActionRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:GroupMembershipAction');
    }

    /**
     * @return \Platformd\GroupBundle\Entity\GroupDiscussionRepository
     */
    private function getGroupDiscussionRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:GroupDiscussion');
    }

    /**
     * @return \Platformd\GroupBundle\Entity\GroupDiscussionPostRepository
     */
    private function getGroupDiscussionPostRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:GroupDiscussionPost');
    }

    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }

    private function getReturnUrl(Request $request)
    {
        return  $request->query->get('return');
    }

    private function getYoutubeManager()
    {
        return $this->get('platformd.model.youtube_manager');
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }
}
