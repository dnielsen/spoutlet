<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupNews;
use Platformd\SpoutletBundle\Entity\GroupVideo;
use Platformd\SpoutletBundle\Entity\GroupImage;
use Platformd\SpoutletBundle\Entity\GroupApplication;
use Platformd\SpoutletBundle\Entity\GroupMembershipAction;
use Platformd\SpoutletBundle\Form\Type\GroupType;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


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

        if (!$group->isAllowedTo($this->getCurrentUser(), $this->getCurrentSite(), $action)) {
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
        ->getRepository('SpoutletBundle:Group')
        ->find($id);
    }

    private function getGroupBySlug($slug) {
        return $this
        ->getEntityManager()
        ->getRepository('SpoutletBundle:Group')
        ->findOneBy(array('slug' => $slug));
    }

    private function getGroupApplicationRepo() {
        return $this
            ->getEntityManager()
            ->getRepository('SpoutletBundle:GroupApplication');
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

        if (!$user) {
            $this->setFlash('error', 'User not found!');
            return $this->redirect($this->generateUrl('group_applications', array('id' => $group->getId())));
        }

        $em = $this->getEntityManager();

        if ($group->isMember($user)) {

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

        $this->getGroupManager()->saveGroup($group);

        $this->sendApplicationAcceptedEmail($application);

        $em->remove($application);
        $em->flush();

        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('joingroup', $user->getId());
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

        if ($group->isMember($user)) {

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

        return $this->render('SpoutletBundle:Group:applications.html.twig', array(
            'applications' => $applications,
            'group' => $group
        ));
    }

    public function indexAction()
    {
        $this->addGroupsBreadcrumb();

        $em     = $this->getEntityManager();
        $repo   = $em->getRepository('SpoutletBundle:Group');
        $site   = $this->getCurrentSite();

        $featuredGroups = $repo->findAllFeaturedGroupsForSite($site);
        $locationGroups = $repo->findGroupsByCategoryAndSite('location', $site);
        $topicGroups    = $repo->findGroupsByCategoryAndSite('topic', $site);
        $recentGroups   = $repo->findMostRecentlyCreatedGroupsForSite($site);
        $popularGroups  = $repo->findMostPopularGroupsForSite($site);

        return $this->render('SpoutletBundle:Group:index.html.twig', array(
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

        if ($group->isOwner($user)) {
            $this->setFlash('error', 'You are the group owner, you are not allowed to leave the group!');
            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
        }

        if (!$group->isMember($user)) {
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

        if ($group->isMember($user) || $group->isOwner($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('groups'));
        }

        $this->ensureAllowed($group, 'JoinGroup');

        $joinAction = new GroupMembershipAction();
        $joinAction->setGroup($group);
        $joinAction->setUser($user);
        $joinAction->setAction(GroupMembershipAction::ACTION_JOINED);

        $group->getMembers()->add($user);
        $group->getUserMembershipActions()->add($joinAction);

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

    public function applyToGroupAction($id, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        if ($group->isMember($user) || $group->isOwner($user)) {
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

        return $this->render('SpoutletBundle:Group:applyToGroup.html.twig', array(
            'group' => $group,
            'form' => $form->createView()));
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

        if ($group->isOwner($user)) {
            $this->setFlash('error', 'You are the group organizer. Please email contact@alienwarearena.com if you want to be removed from this group.');
            return $this->redirect($this->generateUrl('group_members', array('id' => $group->getId())));
        }

        if (!$group->isMember($user)) {
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

        $this->getGroupManager()->saveGroup($group);

        $this->setFlash('success', sprintf('%s is no longer in this group.', $user->getUsername()));

        return $this->redirect($this->generateUrl('group_members', array('id' => $group->getId())));
    }

    public function newsAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewGroupContent', false);

        $groupNews = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);

        return $this->render('SpoutletBundle:Group:news.html.twig', array(
            'group' => $group,
            'groupNews' => $groupNews,
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

        return $this->render('SpoutletBundle:Group:addNews.html.twig', array(
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_add_news', array('id' => $id))
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

        return $this->render('SpoutletBundle:Group:editNews.html.twig', array(
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_edit_news', array('id' => $id, 'newsId' => $newsId)))
        );
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

        return $this->render('SpoutletBundle:Group:images.html.twig', array(
            'pages' => $pages,
            'group' => $group,
        ));
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

        return $this->render('SpoutletBundle:Group:addImage.html.twig', array(
            'group' => $group,
            'imageForm' => $form->createView(),
            'imageFormAction' => $this->generateUrl('group_add_image', array('id' => $id))
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

        return $this->render('SpoutletBundle:Group:editImage.html.twig', array(
            'group' => $group,
            'imageForm' => $form->createView(),
            'imageFormAction' => $this->generateUrl('group_edit_image', array('id' => $id, 'imageId' => $imageId)))
        );
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


        return $this->render('SpoutletBundle:Group:videos.html.twig', array(
            'pages' => $pages,
            'group' => $group,
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

                try {
                    $response = $this->getCEVOApiManager()->GiveUserXp('submitgroupvideo');
                } catch (ApiException $e) {

                }

                $this->setFlash('success', 'New video posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())) . '#videos');
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:addVideo.html.twig', array(
            'group' => $group,
            'videoForm' => $form->createView(),
            'videoFormAction' => $this->generateUrl('group_add_video', array('id' => $id)),
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

        return $this->render('SpoutletBundle:Group:editVideo.html.twig', array(
            'group' => $group,
            'videoForm' => $form->createView(),
            'videoFormAction' => $this->generateUrl('group_edit_video', array('id' => $id, 'videoId' => $videoId)))
        );
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

    public function aboutAction($id)
    {
        $group = $this->getGroup($id);

        return $this->render('SpoutletBundle:Group:about.html.twig', array(
            'group' => $group,
        ));
    }

    public function showAction($slug)
    {
        $this->addGroupsBreadcrumb();
        $user = $this->getCurrentUser();
        $group = $this->getGroupBySlug($slug);

        if ($group->getDeleted()) {
            $this->setFlash('error', 'Sorry, this group does not exist.');
            return $this->redirect($this->generateUrl('groups'));
        }

        $this->ensureAllowed($group, 'ViewGroup', false);

        $groupNews = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);
        $groupVideos = $this->getGroupVideoRepository()->getVideosForGroupMostRecentFirst($group);

        $commentTotal = $this->getTotalCommentCountForGroup('group-'.$group->getId());

        return $this->render('SpoutletBundle:Group:show.html.twig', array(
            'commentTotal' => $commentTotal,
            'group' => $group,
            'groupNews' => $groupNews,
            'groupVideos' => $groupVideos,
        ));
    }

    public function newGettingStartedAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        return $this->render('SpoutletBundle:Group:newGettingStarted.html.twig');
    }

    public function newAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $this->addGroupsBreadcrumb()->addChild('New Group');

        $group  = new Group();
        $form   = $this->createForm(new GroupType($this->getUser(), $group), $group);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The group was created!');

            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('creategroup');
                var_dump($response);
            } catch(ApiException $e) {
                var_dump($e);
            }

            return $this->redirect($this->generateUrl('group_show', array('slug' => $group->getSlug())));
        }

        return $this->render('SpoutletBundle:Group:new.html.twig', array(
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

        return $this->render('SpoutletBundle:Group:edit.html.twig', array(
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

        $repo = $this->getEntityManager()->getRepository('SpoutletBundle:Group');

        $members = $repo->getGroupMembers($id);

        if($currentUser != 'anon.') {
            $canRemove = $currentUser->getAdminLevel() == 'ROLE_SUPER_ADMIN';
        }

        return $this->render('SpoutletBundle:Group:members.html.twig', array(
            'group' => $group,
            'canRemove' => $canRemove,
            'members' => $members,
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
            ->getRepository('CommentBundle:Thread')
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
        return $this->getEntityManager()->getRepository('SpoutletBundle:GroupImage');
    }

    private function getGroupNewsRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:GroupNews');
    }

    private function getGroupVideoRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:GroupVideo');
    }

    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }
}
