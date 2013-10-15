<?php

namespace Platformd\VideoBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\VideoBundle\Entity\YoutubeVideo;
use Platformd\VideoBundle\Entity\YoutubeVote;
use Platformd\VideoBundle\Form\Type\YoutubeType;
use Platformd\GroupBundle\Entity\GroupVideo;
use Platformd\CEVOBundle\Api\ApiException;
use Platformd\UserBundle\Entity\RegistrationSource;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class YoutubeController extends Controller
{
    public function _videoUserInfoAction($id)
    {
        $video = $this->getYoutubeManager()->findVideoById($id);

        if(!$video) {
            throw $this->createNotFoundException($this->trans('youtube.no_video_found'));
        }

        $user    = $this->getUser();

        $voted   = !$this->getYoutubeManager()->canVoteOnVideo($video, $user);
        $isOwner = $video->getAuthor() == $user;

        $response = $this->render('VideoBundle:Youtube:_videoUserInfo.html.twig', array(
            'voted'   => $voted,
            'isOwner' => $isOwner,
        ));

        $this->varnishCache($response, 1);

        return $response;
    }

    public function indexAction()
    {
        $site    = $this->getCurrentSite();
        $country = $this->getCurrentCountry();

        $featured      = $this->getYoutubeManager()->findFeaturedVideosForCountry($site, $country);
        $featuredVideo = count($featured) > 0 ? $featured[0] : null;

        $categoryVideos         = $this->getYoutubeManager()->findVideosForTabAndCountry('categories', $site, $country);
        $popularVideos          = $this->getYoutubeManager()->findVideosForTabAndCountry('popular', $site, $country);
        $currentlyWatchedVideos = $this->getYoutubeManager()->findVideosForTabAndCountry('currently_watched', $site, $country);

        $response = $this->render('VideoBundle:Youtube:index.html.twig', array(
            'featured'               => $featured,
            'featuredVideo'          => $featuredVideo,
            'categoryVideos'         => $categoryVideos,
            'popularVideos'          => $popularVideos,
            'currentlyWatchedVideos' => $currentlyWatchedVideos,
            'regSourceData'          => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_VIDEOS),
        ));

        $this->varnishCache($response, 15);

        return $response;
    }

    public function categoryAction($slug, Request $request)
    {
        $manager  = $this->getYoutubeManager();
        $category = $manager->findCategoryBySlug($slug);

        if(!$category) {
            throw $this->createNotFoundException('No category found.');
        }

        // requirement is to show the most popular videos by category
        $videos = $manager->findPopularVideosByCategoryForCountry($category, $this->getCurrentCountry());

        return $this->render('VideoBundle:Youtube:category.html.twig', array(
            'videos'        => $videos,
            'category'      => $category,
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_VIDEOS),
        ));
    }

    public function submitAction(Request $request, $groupId = null)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user    = $this->getUser();
        $youtube = new YoutubeVideo();
        $form    = $this->createForm('youtube', $youtube);
        $referer = $request->headers->get('referer');

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if($form->isValid()) {
                $video = $form->getData();
                $this->processForm($form, $user);

                $this->setFlash('success', $this->trans('youtube.flash.submit_success'));

                return $this->redirect($this->generateUrl('youtube_view', array('slug' => $youtube->getSlug())));
            } else {
                $this->setFlash('error', $this->trans('youtube.form.general_error'));
            }
        }

        return $this->render('VideoBundle:Youtube:submit.html.twig', array(
            'form'   => $form->createView(),
        ));

    }

    public function editAction(Request $request, $slug)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $tagManager = $this->getTagManager();
        $youtube = $this->getYoutubeManager()->findVideoBySlug($slug);

        if(!$youtube) {
            throw $this->createNotFoundException($this->trans('youtube.no_video_found'));
        }

        $galleries = array();
        foreach ($youtube->getGalleries() as $gallery) {
            $galleries[] = $gallery->getId();
        }
        $youtube->setGalleries($galleries);

        $groups = array();
        foreach ($youtube->getGroups() as $group) {
            $groups[] = $group->getId();
        }
        $youtube->setGroups($groups);

        $tagManager->loadTagging($youtube);

        $user    = $this->getUser();
        $form    = $this->createForm('youtube', $youtube);

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if($form->isValid()) {
                $this->processForm($form, $user, true);

                $referer    = $form['referer']->getData();
                $videoUrl   = $this->generateUrl('youtube_view', array('slug' => $youtube->getSlug()), true);

                if ($referer && $referer != $videoUrl) {
                    $referer = $videoUrl;
                }

                $this->setFlash('success', $this->trans('youtube.flash.edit_success'));
                return $this->redirect($referer);
            }
        }

        return $this->render('VideoBundle:Youtube:edit.html.twig', array(
            'video'     => $youtube,
            'form'      => $form->createView(),
            'referer'   => $request->headers->get('referer'),
        ));
    }

    public function deleteAction(Request $request, $slug)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $youtube = $this->getYoutubeManager()->findVideoBySlug($slug);

        if(!$youtube) {
            throw $this->createNotFoundException($this->trans('youtube.no_video_found'));
        }

        $user = $this->getUser();

        if($youtube->getAuthor() != $user) {
            $this->setFlash('error', $this->trans('youtube.flash.delete_insufficient_permissions'));
            return $this->redirect($this->generateUrl('youtube_view', array('slug' => $youtube->getSlug())));
        }

        $this->getYoutubeManager()->deleteVideo($youtube, YoutubeVideo::DELETED_REASON_BY_AUTHOR);

        $this->setFlash('success', $this->trans('youtube.flash.delete_success'));
        return $this->redirect($this->generateUrl('youtube_index'));
    }

    public function viewAction(Request $request, $slug)
    {
        $video  = $this->getYoutubeManager()->findVideoBySlug($slug);

        if(!$video) {
            throw $this->createNotFoundException($this->trans('youtube.no_video_found'));
        }

        $videos = $this->getYoutubeManager()->findVideosByUser($video->getAuthor(), 3, $video->getId());
        $site = $this->getCurrentSite();
        // if use has only uploaded one video, we show featured videos instead
        $showFeaturedInstead = count($videos) == 0;
        if($showFeaturedInstead) {
            $videos = $this->getYoutubeManager()->findFeaturedVideos($site, 3);
        }

        $video->addView();

        $this->getYoutubeManager()->updateVideo($video);

        $permalink = $this->get('platformd.model.comment_manager')->checkThread($video);

        return $this->render('VideoBundle:Youtube:view.html.twig', array(
            'video'               => $video,
            'videos'              => $videos,
            'showFeaturedInstead' => $showFeaturedInstead,
            'regSourceData'       => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_VIDEOS, 'id'=>$video->getId()),
        ));
    }

    public function voteAction(Request $request)
    {
        $manager  = $this->getYoutubeManager();
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['id']) || !isset($params['voteType'])) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Some required information was not passed.")));
            return $response;
        }

        $id         = (int) $params['id'];
        $voteType   = $params['voteType'];
        $user       = $this->getUser();
        $video      = $manager->findVideoById($id);

        $vote       = new YoutubeVote($user, $video, $voteType, $request->getClientIp(true));

        if (!$this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => 'FORCE_LOGIN_TO_VOTE')));
            return $response;
        }

        if (!in_array($voteType, $vote->getValidVoteTypes())) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Valid vote type (up/down) not given.")));
            return $response;
        }

        if (!$manager->canVoteOnVideo($video, $user)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "You have already voted on this item.")));
            return $response;
        }

        $manager->addVote($vote);

        $likes = count($video->getVotes());

        $response->setContent(json_encode(array("success" => true, "messageForUser" => 'Vote successful', "likes" => $likes)));
        return $response;
    }

    public function tabsAction(Request $request, $tab)
    {
        $site = $this->getCurrentSite();

        $results = $this->getYoutubeManager()->findVideosForTabAndCountry($tab, $site, $this->getCurrentCountry());

        if($tab == 'categories') {
            $response = $this->render('VideoBundle:Youtube:_categoryList.html.twig', array(
                'results' => $results
            ));
        } else {
            $response = $this->render('VideoBundle:Youtube:_tiledList.html.twig', array(
                'videos' => $results
            ));
        }

        $this->varnishCache($response, 30);

        return $response;
    }

    public function categoryTabsAction($cat, $tab, Request $request)
    {
        $category = $this->getYoutubeManager()->findCategoryBySlug($cat);

        $videos = null;

        if($tab == 'popular' && $category) {
            $videos = $this->getYoutubeManager()->findPopularVideosByCategoryForCountry($category, $this->getCurrentCountry());
        }

        if($tab == 'currently_watched' && $category) {
            $videos = $this->getYoutubeManager()->findCurrentlyWatchedVideosByCategoryForCountry($category, $this->getCurrentCountry());
        }

        $response = $this->render('VideoBundle:Youtube:_tiledList.html.twig', array(
            'videos' => $videos
        ));

        $this->varnishCache($response, 30, 30);
        return $response;
    }

    public function featureVideoAction(Request $request)
    {
        $manager  = $this->getYoutubeManager();
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if(empty($content)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if(!isset($params['id']) || !isset($params['featured'])) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Some required information was not passed.")));
            return $response;
        }

        $id         = (int) $params['id'];
        $featured   = $params['featured'];
        $video      = $manager->findVideoById($id);

        if(!$video) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Video not found.")));
            return $response;
        }

        $video->setFeatured($featured);
        $manager->updateVideo($video);

        $messageForUser = $featured ? "Video was featured successfully." : "Video was unfeatured successfully.";

        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('contentfeatured', $video->getAuthor()->getCevoUserId());
        } catch (ApiException $e) {

        }

        $response->setContent(json_encode(array("success" => true, "messageForUser" => $messageForUser, "featured" => $featured ? 1 : 0)));
        return $response;
    }

    public function feedAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $site = $this->getCurrentSite();

        $videos = $this->getYoutubeManager()->findFeaturedVideos($site, 10);
        $results = $this->formatVideosForFeed($videos);

        $response->setContent(json_encode(array('success' => true, 'results' => $results)));
        $this->varnishCache($response, 30);

        return $response;
    }

    private function formatVideosForFeed($videos) {
        $results = array();

        foreach ($videos as $video) {
            array_push($results, array(
                'title'  => $video->getTitle(),
                'thumb'  => $video->getThumbnailSq(),
                'url'    => $this->formatVideoUrl($video),
                'views'  => $video->getViews(),
                'points' => count($video->getVotes()),
            ));
        }

        return $results;
    }

    private function formatVideoUrl($video)
    {
        $site = $this->getCurrentSite();
        $url = sprintf('http://%s%s', $site->getFullDomain(), $this->generateUrl('youtube_view', array('slug' => $video->getSlug())));

        return $url;
    }

    private function processForm($form, $user, $isEdit = false)
    {
        $manager        = $this->getYoutubeManager();
        $tagManager     = $this->getTagManager();
        $groupManager   = $this->getGroupManager();
        $youtube        = $form->getData();
        $siteRepo       = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Site');
        $galleryRepo    = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Gallery');
        $youtube->setYoutubeId($form['youtubeId']->getData());
        $youtube->setDuration($form['duration']->getData());
        $youtube->setSite($this->getCurrentSite());

        if (!$isEdit) {
            $youtube->setAuthor($user);
        }

        $galleries = array();

        foreach ($youtube->getGalleries() as $galleryId) {
            $galleries[] = $galleryRepo->find($galleryId);
        }

        $youtube->setGalleries($galleries);

        if($youtube->getGroups()) {
            $groups = array();

            foreach ($youtube->getGroups() as $groupId) {
                $groups[] = $groupManager->find($groupId);
            }

            $youtube->setGroups($groups);
        }

        $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));

        if($youtube->getId()) {
            $manager->updateVideo($youtube);
            $tagManager->replaceTags($tags, $youtube);
            $tagManager->saveTagging($youtube);

        } else {
            $manager->createVideo($youtube);
            $tagManager->addTags($tags, $youtube);
            $tagManager->saveTagging($youtube);
        }
    }

    private function getYoutubeManager()
    {
        return $this->get('platformd.model.youtube_manager');
    }

    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }

    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }
}
