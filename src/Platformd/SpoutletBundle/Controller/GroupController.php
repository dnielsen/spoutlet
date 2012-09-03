<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupNews;
use Platformd\SpoutletBundle\Entity\GroupVideo;
use Platformd\SpoutletBundle\Entity\GroupImage;
use Platformd\SpoutletBundle\Entity\GroupApplication;
use Platformd\SpoutletBundle\Form\Type\GroupType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Group controller.
 *
 */
class GroupController extends Controller
{
    private function getGroup($id) {
        return $this
        ->getEntityManager()
        ->getRepository('SpoutletBundle:Group')
        ->find($id);
    }

    private function getGroupApplicationRepo() {
        return $this
            ->getEntityManager()
            ->getRepository('SpoutletBundle:GroupApplication');
    }

    private function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }

    /**
     * Lists all Group entities.
     *
     */
    public function indexAction()
    {
        $this->addGroupsBreadcrumb();
        $em = $this->getEntityManager();

        $site = $this->getCurrentSite();

        $entities = $em->getRepository('SpoutletBundle:Group')->findAllGroupsRelevantForSite($site);

        return $this->render('SpoutletBundle:Group:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Leave group.
     *
     */
    public function leaveAction($id)
    {
        $group = $this->getGroup($id);

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $user = $this->getCurrentUser();

        if ($group->getOwner() === $user) {
            $this->setFlash('error', 'You are the group owner, you are not allowed to leave the group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        if (!$group->getMembers()->contains($user)) {
            $this->setFlash('error', 'You are not a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $group->getMembers()->removeElement($user);

        $mgr->saveGroup($group);

        $this->setFlash('success', 'You have successfully left this group!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    /**
     * Join group.
     *
     */
    public function joinAction($id)
    {

        $this->basicSecurityCheck(array('ROLE_USER'));

        $mgr    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $mgr->ensureGroupIsVisible($group);

        if ($group->isMember($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $group->getMembers()->add($user);

        $mgr->saveGroup($group);

        $this->setFlash('success', 'You have successfully joined this group!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    public function applyAction($id)
    {

        $this->basicSecurityCheck(array('ROLE_USER'));

        $mgr    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        if (!$group) {
            throw new NotFoundHttpException('The group does not exist');
        }

        if ($group->isMember($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('group'));
        }

        $applicationRepo  = $this->getGroupApplicationRepo();
        $userApplications = $applicationRepo->findByApplicant($user->getId());

        if ($userApplications) {
            foreach ($userApplications as $app) {

                if ($app->getGroup() && ($app->getGroup()->getId() == $group->getId())) {
                    $this->setFlash('error', 'You have already applied to this group!');
                    return $this->redirect($this->generateUrl('group'));
                }
            }
        }

        $application = new GroupApplication();

        $application->setApplicant($user);
        $application->setGroup($group);

        $em = $this->getEntityManager();
        $em->persist($application);
        $em->flush();

        $this->setFlash('success', 'You have successfully applied to join this group!');

        return $this->redirect($this->generateUrl('group'));
    }

    public function newsAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $mgr = $this->getGroupManager();
        $mgr->ensureGroupIsVisible($group);
        $groupNews = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);
        $userIsAdminOrOwner = $mgr->isCurrentUserAllowedToEditGroup($group);

        return $this->render('SpoutletBundle:Group:news.html.twig', array(
            'group' => $group,
            'groupNews' => $groupNews,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
        ));
    }

    /**
     * View group news.
     *
     */
    public function viewNewsAction($id, $newsId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupNewsRepository();
        $newsArticle = $gr->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        return $this->renderShow($group, array(
            'newsArticle' => $newsArticle,
        ));
    }

    /**
     * Add group news.
     *
     */
    public function addNewsAction($id, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();
        $userIsAdminOrOwner = $gm->isCurrentUserAllowedToEditGroup($group);
        $userIsMember = $gm->isCurrentUserMemberOfGroup($group);

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $groupNews = new GroupNews();

        $form = $this->createFormBuilder($groupNews)
            ->add('title', 'text')
            ->add('article', 'textarea', array(
                'attr' => array('style' => "width: 600px;height: 250px;")
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupNews->setGroup($group);

                $gm->saveGroupNews($groupNews);

                $this->setFlash('success', 'New article posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:addNews.html.twig', array(
            'userIsMember' => $userIsMember,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_add_news', array('id' => $id))
        ));
    }

    /**
     * Edit group news.
     *
     */
    public function editNewsAction($id, $newsId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();
        $userIsAdminOrOwner = $gm->isCurrentUserAllowedToEditGroup($group);
        $userIsMember = $gm->isCurrentUserMemberOfGroup($group);

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupNewsRepository();
        $newsArticle = $gr->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $form = $this->createFormBuilder($newsArticle)
            ->add('title', 'text')
            ->add('article', 'textarea', array(
                'attr' => array('style' => "width: 600px;height: 250px;")
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $newsArticle->setGroup($group);

                $gm->saveGroupNews($newsArticle);

                $this->setFlash('success', 'New article updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:editNews.html.twig', array(
            'userIsMember' => $userIsMember,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_edit_news', array('id' => $id, 'newsId' => $newsId)))
        );
    }

    /**
     * Edit group news.
     *
     */
    public function deleteNewsAction($id, $newsId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupNewsRepository();
        $newsArticle = $gr->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $newsArticle->setDeleted(true);

        $gm->saveGroupNews($newsArticle);

        $this->setFlash('success', 'News article was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    public function imageAction($id, Request $request)
    {
        $group              = $this->getGroup($id);
        $mgr                = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $groupImage         = $this->getGroupImageRepository()->getImagesForGroupMostRecentFirst($group);
        $userIsAdminOrOwner = $mgr->isCurrentUserAllowedToEditGroup($group);

        return $this->render('SpoutletBundle:Group:images.html.twig', array(
            'group' => $group,
            'groupImage' => $groupImage,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
        ));
    }

    /**
     * View group image.
     *
     */
    public function viewImageAction($id, $imageId, Request $request)
    {
        $gm     = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr    = $this->getGroupImageRepository();
        $image = $gr->find($imageId);

        if (!$image) {
            $this->setFlash('error', 'Image does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        return $this->renderShow($group, array(
            'image' => $image,
        ));
    }

    /**
     * Add group image.
     *
     */
    public function addImageAction($id, Request $request)
    {
        $gm     = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();
        $userIsAdminOrOwner = $gm->isCurrentUserAllowedToEditGroup($group);
        $userIsMember = $gm->isCurrentUserMemberOfGroup($group);

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $groupImage = new GroupImage();

        $form = $this->createFormBuilder($groupImage)
            ->add('title', 'text')
            ->add('image', new MediaType(), array('image_label' => 'Image'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupImage->setGroup($group);

                $gm->saveGroupImage($groupImage);

                $this->setFlash('success', 'Image posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:addImage.html.twig', array(
            'userIsMember' => $userIsMember,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
            'group' => $group,
            'imageForm' => $form->createView(),
            'imageFormAction' => $this->generateUrl('group_add_image', array('id' => $id))
        ));
    }

    /**
     * Edit group image.
     *
     */
    public function editImageAction($id, $imageId, Request $request)
    {
        $gm     = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();
        $userIsAdminOrOwner = $gm->isCurrentUserAllowedToEditGroup($group);
        $userIsMember = $gm->isCurrentUserMemberOfGroup($group);


        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupImageRepository();
        $image = $gr->find($imageId);

        if (!$image) {
            $this->setFlash('error', 'Image does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $form = $this->createFormBuilder($image)
            ->add('title', 'text')
            ->add('image', new MediaType(), array('image_label' => 'Image'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $image->setGroup($group);

                $gm->saveGroupImage($image);

                $this->setFlash('success', 'Image updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:editImage.html.twig', array(
            'userIsMember' => $userIsMember,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
            'group' => $group,
            'imageForm' => $form->createView(),
            'imageFormAction' => $this->generateUrl('group_edit_image', array('id' => $id, 'imageId' => $imageId)))
        );
    }

    /**
     * Edit group image.
     *
     */
    public function deleteImageAction($id, $imageId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupImageRepository();
        $image = $gr->find($imageId);

        if (!$image) {
            $this->setFlash('error', 'Image does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $image->setDeleted(true);

        $gm->saveGroupImage($image);

        $this->setFlash('success', 'Image was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    private function renderShow($group, $extraParameters = null) {

        $this->addGroupsBreadcrumb();

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $groupNews = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);
        $groupVideos = $this->getGroupVideoRepository()->getVideosForGroupMostRecentFirst($group);

        $userIsAdminOrOwner = $mgr->isCurrentUserAllowedToEditGroup($group);
        $userIsMember = $mgr->isCurrentUserMemberOfGroup($group);

        $commentTotal = $this->getTotalCommentCountForGroup('group-'.$group->getId());

        if ($commentTotal && isset($commentTotal[0]) && isset($commentTotal[0]['numComments'])) {
            $commentCount = $commentTotal[0]['numComments'];
        } else {
            $commentCount = 0;
        }

        $parameters = array(
            'commentTotal' => $commentCount,
            'group' => $group,
            'groupNews' => $groupNews,
            'groupVideos' => $groupVideos,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
            'userIsMember' => $userIsMember
        );

        if (is_array($extraParameters)) {
            $parameters = array_merge($parameters, $extraParameters);
        }

        return $this->render('SpoutletBundle:Group:show.html.twig', array_merge($parameters, $parameters));
    }

    public function videosAction($id, Request $request)
    {
        $mgr = $this->getGroupManager();
        $group = $this->getGroup($id);
        $groupVideos = $this->getGroupVideoRepository()->getVideosForGroupMostRecentFirst($group);
        $userIsAdminOrOwner = $mgr->isCurrentUserAllowedToEditGroup($group);

        return $this->render('SpoutletBundle:Group:videos.html.twig', array(
            'group' => $group,
            'groupVideos' => $groupVideos,
            'userIsAdminOrOwner' => $userIsAdminOrOwner
        ));
    }

    /**
     * View group news.
     *
     */
    public function viewVideoAction($id, $videoId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupVideoRepository();
        $video = $gr->find($videoId);

        if (!$video) {
            $this->setFlash('error', 'Video does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        return $this->renderShow($group, array(
            'video' => $video,
        ));
    }

    /**
     * Add group video.
     *
     */
    public function addVideoAction($id, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();
        $userIsAdminOrOwner = $gm->isCurrentUserAllowedToEditGroup($group);
        $userIsMember = $gm->isCurrentUserMemberOfGroup($group);

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $groupVideo = new GroupVideo();

        $form = $this->createFormBuilder($groupVideo)
            ->add('title', 'text', array('label' => 'Title'))
            ->add('youTubeVideoId', 'text', array('label' => 'YouTube ID'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupVideo->setGroup($group);

                $groupVideo->setYouTubeThumb($this->getYoutubeThumb($groupVideo->getYouTubeVideoId()));

                $gm->saveGroupVideo($groupVideo);

                $this->setFlash('success', 'New video posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:addVideo.html.twig', array(
            'userIsMember' => $userIsMember,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
            'group' => $group,
            'videoForm' => $form->createView(),
            'videoFormAction' => $this->generateUrl('group_add_video', array('id' => $id)),
        ));
    }

    /**
     * @return string
     */
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

        if(array_key_exists('error', $result)) {
            return '';
        }

        return $result['data']['thumbnail']['sqDefault'];
    }

    /**
     * Edit group video.
     *
     */
    public function editVideoAction($id, $videoId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();
        $userIsAdminOrOwner = $gm->isCurrentUserAllowedToEditGroup($group);
        $userIsMember = $gm->isCurrentUserMemberOfGroup($group);

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupVideoRepository();
        $videoArticle = $gr->find($videoId);

        if (!$videoArticle) {
            $this->setFlash('error', 'Video does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $form = $this->createFormBuilder($videoArticle)
            ->add('title', 'text')
            ->add('youTubeVideoId', 'text')
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $videoArticle->setGroup($group);

                $gm->saveGroupVideo($videoArticle);

                $this->setFlash('success', 'Video updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:editVideo.html.twig', array(
            'userIsMember' => $userIsMember,
            'userIsAdminOrOwner' => $userIsAdminOrOwner,
            'group' => $group,
            'videoForm' => $form->createView(),
            'videoFormAction' => $this->generateUrl('group_edit_video', array('id' => $id, 'videoId' => $videoId)))
        );
    }

    /**
     * Edit group video.
     *
     */
    public function deleteVideoAction($id, $videoId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupVideoRepository();
        $videoArticle = $gr->find($videoId);

        if (!$videoArticle) {
            $this->setFlash('error', 'Video does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $em = $this->getEntityManager();
        $em->remove($videoArticle);
        $em->flush();

        $this->setFlash('success', 'Video was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    /**
     * Shows a Group entitie.
     *
     */
    public function showAction($id)
    {
        $this->addGroupsBreadcrumb();

        $group = $this->getGroup($id);

        return $this->renderShow($group);
    }

     /**
     * Creates a new Group (getting started).
     *
     */
    public function newGettingStartedAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        return $this->render('SpoutletBundle:Group:newGettingStarted.html.twig');
    }

    /**
     * Creates a new Group.
     *
     */
    public function newAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $this->addGroupsBreadcrumb()->addChild('New Group');

        $group  = new Group();
        $form    = $this->createForm(new GroupType(), $group);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The group was created!');

            return $this->redirect($this->generateUrl('group_edit', array('id' => $group->getId())));
        }

        return $this->render('SpoutletBundle:Group:new.html.twig', array(
            'entity' => $group,
            'form'   => $form->createView()
        ));
    }

    /**
     * Edits an existing Group group.
     *
     */
    public function editAction($id, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $this->addGroupsBreadcrumb()->addChild('Edit Group');

        $group = $this->getGroup($id);

        $user = $this->getUser();

        if ($group->getOwner() !== $user) {
            $this->setFlash('error', 'You are not the owner of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
        }

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $editForm   = $this->createForm(new GroupType(), $group);
        $deleteForm = $this->createDeleteForm($id);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The group was saved!');

            return $this->redirect($this->generateUrl('group_edit', array('id' => $id)));
        }

        return $this->render('SpoutletBundle:Group:edit.html.twig', array(
            'group'      => $group,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     * Delete entity
     */
    public function deleteAction($id)
    {
        $group = $this->getGroup($id);
        $user = $this->getUser();

        if ($group->getOwner() !== $user) {
            $this->setFlash('error', 'You are not the owner of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
        }

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $group->setDeleted(true);

        $mgr->saveGroup($group);

        $this->setFlash('success', 'The group was successfully deleted!');

        return $this->redirect($this->generateUrl('group'));
    }

    private function processForm(Form $form, Request $request)
    {

        $em = $this->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                /** @var $group \Platformd\SpoutletBundle\Entity\Group */
                $group = $form->getData();

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

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addGroupsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Groups', array(
            'route' => 'group'
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Platformd\SpoutletBundle\Model\GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
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
}
