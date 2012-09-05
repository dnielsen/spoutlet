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
            throw new AccessDeniedException('You are not allowed/eligible to do that.');
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

        if(array_key_exists('error', $result)) {
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

    private function getGroupApplicationRepo() {
        return $this
            ->getEntityManager()
            ->getRepository('SpoutletBundle:GroupApplication');
    }

    private function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }

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

    public function leaveAction($id)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);
        $user = $this->getCurrentUser();

        if ($group->isOwner($user)) {
            $this->setFlash('error', 'You are the group owner, you are not allowed to leave the group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        if (!$group->isMember($user)) {
            $this->setFlash('error', 'You are not a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $this->ensureAllowed($group, 'LeaveGroup');

        $group->getMembers()->removeElement($user);

        $this->getGroupManager()->saveGroup($group);

        $this->setFlash('success', 'You have successfully left this group!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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

        $group->getMembers()->add($user);

        $this->getGroupManager()->saveGroup($group);

        $this->setFlash('success', 'You have successfully joined this group!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    public function applyAction($id)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $group = $this->getGroup($id);

        $this->ensureGroupExists($group);

        $user = $this->getUser();

        if ($group->isMember($user) || $group->isOwner($user)) {
            $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('groups'));
        }

        $userApplications = $this->getGroupApplicationRepo()->findByApplicant($user->getId());

        if ($userApplications) {
            foreach ($userApplications as $app) {

                if ($app->getGroup() && ($app->getGroup()->getId() == $group->getId())) {
                    $this->setFlash('error', 'You have already applied to this group!');
                    return $this->redirect($this->generateUrl('groups'));
                }
            }
        }

        $this->ensureAllowed($group, 'ApplyToGroup');

        $application = new GroupApplication();

        $application->setApplicant($user);
        $application->setGroup($group);

        $em = $this->getEntityManager();
        $em->persist($application);
        $em->flush();

        $this->setFlash('success', 'You have successfully applied to join this group!');

        return $this->redirect($this->generateUrl('groups'));
    }

    public function newsAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewGroup', false);

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
            ->add('article', 'textarea', array(
                'attr' => array('style' => "width: 600px;height: 250px;")
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupNews->setGroup($group);

                $this->getGroupManager()->saveGroupNews($groupNews);

                $this->setFlash('success', 'New article posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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

                $this->getGroupManager()->saveGroupNews($newsArticle);

                $this->setFlash('success', 'New article updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $newsArticle->setDeleted(true);

        $this->getGroupManager()->saveGroupNews($newsArticle);

        $this->setFlash('success', 'News article was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    public function imageAction($id, Request $request)
    {
        $group = $this->getGroup($id);
        $this->ensureAllowed($group, 'ViewGroup', false);

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

                $this->setFlash('success', 'Image posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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

                $this->getGroupManager()->saveGroupImage($image);

                $this->setFlash('success', 'Image updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $image->setDeleted(true);

        $this->getGroupManager()->saveGroupImage($image);

        $this->setFlash('success', 'Image was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    public function videosAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $this->ensureAllowed($group, 'ViewGroup', false);

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

                $this->setFlash('success', 'New video posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $em = $this->getEntityManager();
        $em->remove($videoArticle);
        $em->flush();

        $this->setFlash('success', 'Video was deleted successfully!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    public function showAction($id)
    {
        $this->addGroupsBreadcrumb();

        $group = $this->getGroup($id);

        $this->addGroupsBreadcrumb();

        $this->ensureAllowed($group, 'ViewGroup', false);

        $groupNews = $this->getGroupNewsRepository()->getNewsForGroupMostRecentFirst($group);
        $groupVideos = $this->getGroupVideoRepository()->getVideosForGroupMostRecentFirst($group);

        $commentTotal = $this->getTotalCommentCountForGroup('group-'.$group->getId());

        if ($commentTotal && isset($commentTotal[0]) && isset($commentTotal[0]['numComments'])) {
            $commentCount = $commentTotal[0]['numComments'];
        } else {
            $commentCount = 0;
        }

        return $this->render('SpoutletBundle:Group:show.html.twig', array(
            'commentTotal' => $commentCount,
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
        $form    = $this->createForm(new GroupType($this->getUser()), $group);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The group was created!');

            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
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

        $editForm = $this->createForm(new GroupType($this->getUser()), $group);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The group was saved!');

            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
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

    private function processForm(Form $form, Request $request)
    {

        $em = $this->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $group = $form->getData();

                if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
                    $group->setAllLocales(false);
                    $group->getSites()->clear();
                    $group->getSites()->add($this->getCurrentSite());
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
