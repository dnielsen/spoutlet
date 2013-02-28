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

/**
 * Group controller.
 *
 */
class GroupController extends Controller
{

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

    private function getGroup($id) {
        return $this
        ->getEntityManager()
        ->getRepository('GroupBundle:Group')
        ->find($id);
    }

    private function getGroupBySlug($slug) {
        return $this
        ->getEntityManager()
        ->getRepository('GroupBundle:Group')
        ->findOneBy(array('slug' => $slug));
    }

    private function getGroupApplicationRepo() {
        return $this
            ->getEntityManager()
            ->getRepository('GroupBundle:GroupApplication');
    }

    private function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }

    private function sendApplicationAcceptedEmail($application) {

        $currentHost        = $this->getRequest()->getHost();
        $currentSubDomain   = substr($currentHost, 0, stripos($currentHost, '.'));
        $applicantSubDomain = $application->getSite()->getSubDomain();

        if (strpos($currentSubDomain, 'staging') !== false) {
            $applicantSubDomain .= 'staging';
        }

        $baseHost          = $this->container->getParameter('base_host');

        $groupName          = $application->getGroup()->getName();
        $groupUrlRelative   = $this->generateUrl('group_show', array('slug' => $application->getGroup()->getSlug()));
        $groupUrlAbsolute   = sprintf('http://%s.%s%s', $applicantSubDomain, $baseHost, $groupUrlRelative);

        $fromEmail          = $this->container->getParameter('sender_email_address');
        $fromName           = $this->container->getParameter('sender_email_name');

        $subject            = "You’re approved to be in an Alienware Arena Group!";
        $message            = sprintf("Congratulations!  You’re now a member of \"%s\" (Group Page: %s).

Visit your group's page to join in on the conversation and share information with your group members.

Note: You are receiving this message because you submitted a request to join a private group.

Alienware Arena Team
", $groupName, $groupUrlAbsolute);

        $emailTo = $application->getApplicant()->getEmail();

        $this->getEmailManager()->sendEmail($emailTo, $subject, $message, "Group Application Approved", $this->getCurrentSite()->getDefaultLocale(), $fromName, $fromEmail);
    }

    public function acceptApplicationAction($id, $applicationId) {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ManageApplications');

        $appRepo = $this->getGroupApplicationRepo();

        $application = $appRepo->find($applicationId);

        if (!$application) {
            $this->setFlash('error', 'Application not found!');
            return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
        }

        $user = $application->getApplicant();
        $event = $application->getEvent();

        if (!$user) {
            $this->setFlash('error', 'User not found!');
            return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
        }

        $em = $this->getEntityManager();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group)) {

            $em->remove($application);
            $em->flush();

            $this->setFlash('success', 'This user is already a member of this group - application removed!');
            return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
        }

        $joinAction = new GroupMembershipAction();
        $joinAction->setGroup($group);
        $joinAction->setUser($user);
        $joinAction->setAction(GroupMembershipAction::ACTION_JOINED_APPLICATION_ACCEPTED);

        $group->getMembers()->add($user);
        $group->getUserMembershipActions()->add($joinAction);

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
            $subject     = $this->trans('platformd.event.email.group_application_accepted_event_registered.title', array(), 'messages', $emailLocale);
            $message     = nl2br($this->trans('platformd.event.email.group_application_accepted_event_registered.message', array(
                '%groupUrl%' => $this->generateUrl($group->getLinkableRouteName(), $group->getLinkableRouteParameters(), true),
                '%groupName%' => $group->getName(),
                '%eventUrl%' => $this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
                '%eventName%' => $event->getName(),
            ), 'messages', $emailLocale));

            $emailTo = $user->getEmail();

            $this->getEmailManager()->sendHtmlEmail($emailTo, $subject, $message, "Group Application Notification", $this->getCurrentSite()->getDefaultLocale());
        }

        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('joingroup', $user->getCevoUserId());
        } catch (ApiException $e) {

        }

        $this->setFlash('success', sprintf('You have successfully accepted \'%s\' into your group!', $user->getUsername()));

        return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
    }

    public function rejectApplicationAction($id, $applicationId) {

        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ManageApplications');

        $appRepo = $this->getGroupApplicationRepo();

        $application = $appRepo->find($applicationId);

        if (!$application) {
            $this->setFlash('error', 'Application not found!');
            return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
        }

        $user = $application->getApplicant();

        if (!$user) {
            $this->setFlash('error', 'User not found!');
            return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
        }

        $em = $this->getEntityManager();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group)) {

            $em->remove($application);
            $em->flush();

            $this->setFlash('error', 'This user is already a member of this group - application removed!');
            return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
        }

        $em->remove($application);
        $em->flush();

        $this->setFlash('success', sprintf('You have successfully rejected \'%s\' from joining your group!', $user->getUsername()));

        return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
    }

    public function applicationsAction($id) {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ManageApplications');

        $appRepo = $this->getGroupApplicationRepo();

        $applications = $appRepo->getApplicationsForGroup($group);

        return $this->render('GroupBundle:Group:applications.html.twig', array(
            'applications' => $applications,
            'group' => $group,
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function indexAction()
    {
        $this->addGroupsBreadcrumb();

        $em     = $this->getEntityManager();
        $repo   = $em->getRepository('GroupBundle:Group');
        $site   = $this->getCurrentSite();

        $featuredGroups = $repo->findAllFeaturedGroupsForSite($site);
        $locationGroups = $repo->findGroupsByCategoryAndSite('location', $site);
        $topicGroups    = $repo->findGroupsByCategoryAndSite('topic', $site);
        $recentGroups   = $repo->findMostRecentlyCreatedGroupsForSite($site);
        $popularGroups  = $repo->findMostPopularGroupsForSite($site);

        return $this->render('GroupBundle:Group:index.html.twig', array(
            'locationGroups' => $this->getGroupPages($locationGroups),
            'topicGroups'    => $this->getGroupPages($topicGroups),
            'recentGroups'   => $recentGroups,
            'popularGroups'  => $popularGroups,
            'featuredGroups' => $featuredGroups,
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

    public function leaveAction($id)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);
        $user = $this->getCurrentUser();

        $groupManager = $this->getGroupManager();

        if ($group->isOwner($user)) {
            $this->setFlash('error', 'You are the group owner, you are not allowed to leave the group!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
        }

        if (!$groupManager->isMember($user, $group)) {
            $this->setFlash('error', 'You are not a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
        }

        $this->ensureAllowed($group, 'LeaveGroup');

        $leaveAction = new GroupMembershipAction();
        $leaveAction->setGroup($group);
        $leaveAction->setUser($user);
        $leaveAction->setAction(GroupMembershipAction::ACTION_LEFT);

        $group->getMembers()->removeElement($user);
        $group->getUserMembershipActions()->add($leaveAction);

        // TODO Add a service layer for managing groups and dispatching such events
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
        $dispatcher = $this->get('event_dispatcher');
        $event = new GroupEvent($group, $user);
        $dispatcher->dispatch(GroupEvents::GROUP_LEAVE, $event);

        $this->getGroupManager()->saveGroup($group);

        $this->setFlash('success', 'You have successfully left this group!');

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
    }

    public function joinAction($id)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group) || $group->isOwner($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('groups'));
        }

        $this->ensureAllowed($group, 'JoinGroup');

        // TODO This should probably be refactored to use the global activity table
        $joinAction = new GroupMembershipAction();
        $joinAction->setGroup($group);
        $joinAction->setUser($user);
        $joinAction->setAction(GroupMembershipAction::ACTION_JOINED);

        $group->getMembers()->add($user);
        $group->getUserMembershipActions()->add($joinAction);

        // TODO Add a service layer for managing groups and dispatching such events
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
        $dispatcher = $this->get('event_dispatcher');
        $event = new GroupEvent($group, $user);
        $dispatcher->dispatch(GroupEvents::GROUP_JOIN, $event);

        $this->getGroupManager()->saveGroup($group);

        //$this->setFlash('success', 'You will receive an email if you are admitted into this group.');

        if($group->getIsPublic()) {
            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('joingroup');
            } catch (ApiException $e) {

            }
        }

        $this->setFlash('success', 'You have successfully joined this group!');

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
    }

    public function applyToGroupWithEventAction($id, $eventId, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        if (!$event = $this->getGroupEventService()->find($eventId)) {
            $this->setFlash('error', 'Group event not found!');
            return $this->redirect($this->generateUrl($group->getLinkableRouteName(), $grpup->getLinkableRouteParameters()));
        }

        if ($group->isMember($user) || $group->isOwner($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
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
            '%approvalUrl%' => $this->generateUrl('group_applications', array('id' => $group->getId()), true),
        ), 'messages', $emailLocale));

        $emailTo = $group->getOwner()->getEmail();

        $this->getEmailManager()->sendHtmlEmail($emailTo, $subject, $message, "Group Application Notification", $this->getCurrentSite()->getDefaultLocale());

        $this->setFlash('success', 'Your application has been made. You will be notified if your application is successful.');
        return $this->redirect($this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters()));
    }

    public function applyToGroupAction($id, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        $groupManager = $this->getGroupManager();

        if ($groupManager->isMember($user, $group) || $group->isOwner($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
        }

        $userApplications = $this->getGroupApplicationRepo()->findByApplicant($user->getId());

        if ($userApplications) {
            foreach ($userApplications as $app) {

                if ($app->getGroup() && ($app->getGroup()->getId() == $group->getId())) {
                    $this->setFlash('error', 'You have already applied to this group!');
                    return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
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

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:applyToGroup.html.twig', array(
            'group' => $group,
            'form' => $form->createView(),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function removeAction($id, $uid)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);
        $user = null;

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
            return $this->redirect($this->generateUrl('group_members', array('id' => $group->getId())));
        }

        $groupManager = $this->getGroupManager();

        if ($group->isOwner($user)) {
            $this->setFlash('error', 'You are the group organizer. Please email contact@alienwarearena.com if you want to be removed from this group.');
            return $this->redirect($this->generateUrl('group_members', array('id' => $group->getId())));
        }

        if (!$groupManager->isMember($user, $group)) {
            $this->setFlash('error', 'You cannot remove someone who is not a member of this group!');
            return $this->redirect($this->generateUrl('group_members', array('id' => $group->getId())));
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

        return $this->redirect($this->generateUrl('group_members', array('id' => $group->getId())));
    }

    public function eventsAction($id)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $groupEvents    = $this->getGroupEventService()->findUpcomingEventsForGroupMostRecentFirst($group);
        $pastEvents     = $this->getGroupEventService()->findPastEventsForGroupMostRecentFirst($group);

        return $this->render('GroupBundle:Group:events.html.twig', array(
            'group'         => $group,
            'groupEvents'   => $groupEvents,
            'pastEvents'    => $pastEvents,
        ));
    }

    public function newsAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $groupNews = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);

        return $this->render('GroupBundle:Group:news.html.twig', array(
            'group' => $group,
            'groupNews' => $groupNews,
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function addNewsAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'AddNews');

        $groupNews = new GroupNews();

        $form = $this->createFormBuilder($groupNews)
            ->add('title', 'text')
            ->add('article', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor')
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupNews->setGroup($group);

                $this->getGroupManager()->saveGroupNews($groupNews);

                $this->setFlash('success', 'New article posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#news');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:addNews.html.twig', array(
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_add_news', array('id' => $id)),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function editNewsAction($id, $newsId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'EditNews');

        $newsArticle = $this->getGroupNewsRepository()->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#news');
        }

        $form = $this->createFormBuilder($newsArticle)
            ->add('title', 'text')
            ->add('article', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor')
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $newsArticle->setGroup($group);

                $this->getGroupManager()->saveGroupNews($newsArticle);

                $this->setFlash('success', 'New article updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#news');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:editNews.html.twig', array(
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_edit_news', array('id' => $id, 'newsId' => $newsId)),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function deleteNewsAction($id, $newsId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'DeleteNews');

        $newsArticle = $this->getGroupNewsRepository()->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#news');
        }

        $newsArticle->setDeleted(true);

        $this->getGroupManager()->saveGroupNews($newsArticle);

        $this->setFlash('success', 'News article was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#news');
    }

    public function imageAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $groupImage         = $this->getGroupImageRepository()->getImagesForGroupMostRecentFirst($group);

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

        return $this->render('GroupBundle:Group:images.html.twig', array(
            'pages' => $pages,
            'group' => $group,
            'groupManager' => $this->getGroupManager(),
        ));
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

            return $this->render('GroupBundle:Group:showImage.html.twig', array(
                'media'             => $groupImage,
                'group'             => $group,
                'otherMediaPages'   => $otherMediaPages,
                'galleryMediaItem'  => $galleryMedia,
                'groupManager' => $this->getGroupManager(),
            ));
        }

        $this->setFlash('error', 'Group image not found!');
        return $this->redirect($this->generateUrl('groups'));
    }

    public function addImageAction($id, Request $request)
    {
        $group = $this->getGroup($id);
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
                    $response = $this->getCEVOApiManager()->GiveUserXp('submitgroupphoto');
                } catch (ApiException $e) {

                }

                $this->setFlash('success', 'Image posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#images');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:addImage.html.twig', array(
            'group' => $group,
            'imageForm' => $form->createView(),
            'imageFormAction' => $this->generateUrl('group_add_image', array('id' => $id)),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function editImageAction($id, $imageId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'EditImage');

        $image = $this->getGroupImageRepository()->find($imageId);

        if (!$image) {
            $this->setFlash('error', 'Image does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#images');
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

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#images');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:editImage.html.twig', array(
            'group' => $group,
            'imageForm' => $form->createView(),
            'imageFormAction' => $this->generateUrl('group_edit_image', array('id' => $id, 'imageId' => $imageId)),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function deleteImageAction($id, $imageId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'DeleteImage');

        $image = $this->getGroupImageRepository()->find($imageId);

        if (!$image) {
            $this->setFlash('error', 'Image does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#images');
        }

        $image->setDeleted(true);

        $this->getGroupManager()->saveGroupImage($image);

        $this->setFlash('success', 'Image was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#images');
    }

    public function videosAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $groupVideos = $this->getGroupVideoRepository()->getVideosForGroupMostRecentFirst($group);

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


        return $this->render('GroupBundle:Group:videos.html.twig', array(
            'pages' => $pages,
            'group' => $group,
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function addVideoAction($id, Request $request)
    {
        $group = $this->getGroup($id);
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
                    $response = $api->GiveUserXp('submitgroupvideo');
                } catch (ApiException $e) {

                }

                $this->setFlash('success', 'New video posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#videos');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:addVideo.html.twig', array(
            'group' => $group,
            'videoForm' => $form->createView(),
            'videoFormAction' => $this->generateUrl('group_add_video', array('id' => $id)),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function editVideoAction($id, $videoId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'EditVideo');

        $video = $this->getGroupVideoRepository()->find($videoId);

        if (!$video) {
            $this->setFlash('error', 'Video does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#videos');
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

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#videos');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:editVideo.html.twig', array(
            'group' => $group,
            'videoForm' => $form->createView(),
            'videoFormAction' => $this->generateUrl('group_edit_video', array('id' => $id, 'videoId' => $videoId)),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function deleteVideoAction($id, $videoId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'DeleteVideo');

        $videoArticle = $this->getGroupVideoRepository()->find($videoId);

        if (!$videoArticle) {
            $this->setFlash('error', 'Video does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#videos');
        }

        $em = $this->getEntityManager();
        $em->remove($videoArticle);
        $em->flush();

        $this->setFlash('success', 'Video was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#videos');
    }

    public function discussionsAction($id)
    {
        $group = $this->getGroup($id);

        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $request = $this->getRequest();
        $page = $request->query->get('page', 1);

        $pager = $this->getGroupDiscussionRepository()->getDiscussionsForGroupMostRecentFirst($group, 11, $page);
        $results = $pager->getCurrentPageResults();

        return $this->render('GroupBundle:Group:discussions.html.twig', array(
            'group' => $group,
            'results' => $results,
            'pager' => $pager,
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function enableDiscussionsAction($id)
    {
        $group = $this->getGroup($id);

        $this->ensureAllowed($group, 'ManageDiscussions');

        $group->setDiscussionsEnabled(true);
        $this->getGroupManager()->saveGroup($group);

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
    }

    public function disableDiscussionsAction($id)
    {
        $group = $this->getGroup($id);

        $this->ensureAllowed($group, 'ManageDiscussions');

        $group->setDiscussionsEnabled(false);
        $this->getGroupManager()->saveGroup($group);

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
    }

    public function addDiscussionAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'AddDiscussion');

        $groupDiscussion = new GroupDiscussion();

        $form = $this->createFormBuilder($groupDiscussion)
            ->add('title', 'text', array('label' => 'Discussion Name'))
            ->add('content', 'purifiedTextarea', array(
                'label' => 'Content',
                'help'  => 'Give a description for your discussion.',
                'required' => true,
                'attr'  => array('class' => 'ckeditor'),
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupDiscussion->setGroup($group);

                $this->getGroupManager()->createGroupDiscussion($groupDiscussion);

                $this->setFlash('success', 'New discussion posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:addDiscussion.html.twig', array(
            'group' => $group,
            'discussionForm' => $form->createView(),
            'discussionFormAction' => $this->generateUrl('group_add_discussion', array('id' => $id)),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function editDiscussionAction($id, $discussionId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'EditDiscussion');

        $discussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$discussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
        }

        $form = $this->createFormBuilder($discussion)
            ->add('title', 'text', array('label' => 'Discussion Name'))
            ->add('content', 'purifiedTextarea', array(
                    'label' => 'Content',
                    'help'  => 'Give a description for your discussion.',
                    'attr'  => array('class' => 'ckeditor'),
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $discussion->setGroup($group);

                $this->getGroupManager()->updateGroupDiscussion($discussion);

                $this->setFlash('success', 'Discussion updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:editDiscussion.html.twig', array(
            'group' => $group,
            'discussionForm' => $form->createView(),
            'discussionFormAction' => $this->generateUrl('group_edit_discussion', array('id' => $id, 'discussionId' => $discussionId)),
            'discussionId' => $discussionId,
            'groupManager' => $this->getGroupManager(),
            )
        );
    }

    public function deleteDiscussionAction($id, $discussionId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'DeleteDiscussion');

        $discussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$discussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
        }

        $this->getGroupManager()->deleteGroupDiscussion($discussion);

        $this->setFlash('success', 'Discussion was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
    }

    public function viewDiscussionAction($id, $discussionId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewDiscussion');

        $groupDiscussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$groupDiscussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
        }

        // We increment viewCount
        $this->getGroupManager()->viewGroupDiscussion($groupDiscussion, $request->getSession(), $this->getCurrentUser());

        $page = $request->query->get('page', 1);

        $pager = $this->getGroupDiscussionPostRepository()->getDiscussionPostsMostRecentFirst($groupDiscussion, 10, $page);
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
                    'id' => $id,
                    'discussionId' => $discussionId
                )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:viewDiscussion.html.twig', array(
            'discussion' => $groupDiscussion,
            'discussionPosts' => $groupDiscussionPosts,
            'group' => $group,
            'pager' => $pager,
            'replyForm' => $form->createView(),
            'replyFormAction' => $this->generateUrl('group_view_discussion', array(
                'id' => $id,
                'discussionId' => $discussionId
            )),
            'groupManager' => $this->getGroupManager(),
        ));
    }

    public function replyDiscussionAction($id, $discussionId, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewDiscussion');

        $groupDiscussion = $this->getGroupDiscussionRepository()->find($discussionId);

        if (!$groupDiscussion) {
            $this->setFlash('error', 'Discussion does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#discussions');
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
                    'id' => $id,
                    'discussionId' => $discussionId
                )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:replyDiscussion.html.twig', array(
            'group' => $group,
            'discussion' => $groupDiscussion,
            'replyForm' => $form->createView(),
            'replyFormAction' => $this->generateUrl('group_reply_discussion', array(
                'id' => $id,
                'discussionId' => $discussionId
            )),
            'groupManager' => $this->getGroupManager(),
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
                    'id' => $group->getId(),
                    'discussionId' => $groupDiscussion->getId()
                )));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('GroupBundle:Group:replyDiscussion.html.twig', array(
            'group' => $group,
            'discussion' => $groupDiscussion,
            'replyForm' => $form->createView(),
            'replyFormAction' => $this->generateUrl('group_edit_discussion_post', array(
                'id' => $id,
                'discussionId' => $groupDiscussion->getId()
            )),
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
            'id' => $group->getId(),
            'discussionId' => $groupDiscussion->getId()
        )));
    }

    public function aboutAction($id)
    {
        $group = $this->getGroup($id);

        return $this->render('GroupBundle:Group:about.html.twig', array(
            'group' => $group,
        ));
    }

    public function showAction($slug)
    {
        $this->addGroupsBreadcrumb();

        $user   = $this->getCurrentUser();
        $group  = $this->getGroupBySlug($slug);

        if ($group && $group->getDeleted()) {
            $this->setFlash('error', 'Sorry, this group does not exist.');
            return $this->redirect($this->generateUrl('groups'));
        }

        $this->ensureAllowed($group, 'ViewGroup', false);

        $groupNews      = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);
        $groupVideos    = $this->getGroupVideoRepository()->getVideosForGroupMostRecentFirst($group);
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

        return $this->render('GroupBundle:Group:show.html.twig', array(
            'commentTotal'  => $commentTotal,
            'group'         => $group,
            'groupNews'     => $groupNews,
            'groupVideos'   => $groupVideos,
            'isEntered'     => $isEntered,
            'contestCount'  => $contestMemberCount,
            'contest'       => $contest,
            'upcomingEvents'    => $upcomingEvents,
            'pastEvents'        => $pastEvents,
            'memberCount'   => $memberCount[0]['membershipCount'],
            'groupManager'  => $this->getGroupManager(),
        ));
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

        $group  = new Group();

        // assume that the group is only being created because the user wants to do something that requires a group first and they have none.
        if ($then = $request->query->get('then')) {
            $request->getSession()->set(
                'PostCreateAction',
                $then
            );

            $group->setDescription('Welcome to my group! <br /><br />This is the place to share your thoughts with like-minded folks on this topic. <br /><br />Feel free to upload relevant images and videos, or start a discussion on this topic.');
        }

        $form   = $this->createForm(new GroupType($this->getUser(), $group), $group);

        if($previous = $this->getReturnUrl($request)) {
            $request->getSession()->set(
                'ContestReturnUrl',
                $previous
            );
        }

        if ($this->processForm($form, $request)) {

            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('creategroup');
            } catch(ApiException $e) {
            }

            if($then = $request->getSession()->get('PostCreateAction')) {

                if ($then == "group_event") {

                    $this->setFlash('success', 'Your group was created. Fill in the details below to list your upcoming event.');

                    $url = $this->generateUrl('group_event_new', array('groupSlug' => $group->getSlug()));
                    $request->getSession()->remove('PostCreateAction');
                    return $this->redirect($url);
                }
            }

            $this->setFlash('success', 'The group was created!');

            if($return = $request->getSession()->get('ContestReturnUrl')) {
                $request->getSession()->remove('ContestReturnUrl');
                return $this->redirect($return);
            }

            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
        }

        return $this->render('GroupBundle:Group:new.html.twig', array(
            'entity' => $group,
            'form'   => $form->createView()
        ));
    }

    public function editAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'EditGroup');

        $this->addGroupsBreadcrumb()->addChild('Edit Group');

        $editForm = $this->createForm(new GroupType($this->getUser(), $group), $group);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The group was saved!');

            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
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

    public function deleteAction($id)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'DeleteGroup');

        $group->setDeleted(true);

        $this->getGroupManager()->saveGroup($group);

        $this->setFlash('success', 'The group was successfully deleted!');

        return $this->redirect($this->generateUrl('groups'));
    }

    public function membersAction($id) {
        $group = $this->getGroup($id);
        $currentUser = $this->getCurrentUser();
        $canRemove = $group->isOwner($currentUser) && $currentUser != 'anon.';

        $repo = $this->getEntityManager()->getRepository('GroupBundle:Group');

        $members = $repo->getGroupMembers($id);

        if($currentUser != 'anon.') {
            $canRemove = $currentUser->getAdminLevel() == 'ROLE_SUPER_ADMIN';
        }

        return $this->render('GroupBundle:Group:members.html.twig', array(
            'group' => $group,
            'canRemove' => $canRemove,
            'members' => $members,
            'groupManager' => $this->getGroupManager(),
        ));
    }

    private function processForm(Form $form, Request $request)
    {

        $em = $this->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $group = $form->getData();

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

                $this->getGroupManager()->saveGroup($group);

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

    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

     private function getEmailManager()
    {
        return $this->get('platformd.model.email_manager');
    }

    private function getEntityManager() {
        return $this->getDoctrine()->getEntityManager();
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

    /**
     * @return \Platformd\EventBundle\Service\GroupEventService
     */
    private function getGroupEventService()
    {
        return $this->get('platformd_event.service.group_event');
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
}
