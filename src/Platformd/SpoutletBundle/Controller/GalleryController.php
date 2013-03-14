<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\MediaGallery;
use Platformd\SpoutletBundle\Entity\GalleryMedia;
use Platformd\GroupBundle\Entity\GroupImage;
use Platformd\SpoutletBundle\Entity\Vote;
use Platformd\SpoutletBundle\Form\Type\SubmitImageType;
use Platformd\SpoutletBundle\Form\Type\GalleryChoiceType;
use Platformd\SpoutletBundle\Form\Type\GalleryMediaType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Platformd\SpoutletBundle\Util\StringUtil;
use Platformd\UserBundle\Entity\User;
use Platformd\MediaBundle\Imagine\Cache\Resolver\AmazonS3Resolver;

/**
 * Gallery controller.
 *
 */
class GalleryController extends Controller
{
    public function indexAction(Request $request)
    {
        $nivoSliderMedia    = $this->getGalleryMediaRepository()->findFeaturedMediaForSite($this->getCurrentSite(), 5);
        $options            = $this->getFilterOptions();
        $user               = $this->getUser();
        $mediaId            = (int)$request->query->get('vote');

        if ($mediaId && $this->isGranted('ROLE_USER')) {
            $media = $this->getGalleryMediaRepository()->find($mediaId);

            if ($media && !$media->hasUserVoted($this->getUser())) {
                $vote = new Vote();
                $vote->setUser($this->getUser());
                $vote->setGalleryMedia($media);
                $vote->setVoteType('up');
                $vote->setIpAddress($request->getClientIp(true));

                $em = $this->getEntityManager();

                $em->persist($vote);

                $media->getVotes()->add($vote);
                $em->persist($media);
                $em->flush();
            }
        }

        return $this->render('SpoutletBundle:Gallery:index.html.twig', array(
            'nivoSliderMedia'   => $nivoSliderMedia,
            'filterOptions'     => $options,
        ));
    }

    public function submitAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user       = $this->getCurrentUser();

        $form       = $this->createForm(new SubmitImageType($user));

        $medias     = $this->getGalleryMediaRepository()->findAllUnpublishedByUser($user);
        $galleries  = $this->getGalleryRepository()->findAllGalleriesByCategoryForSite($this->getCurrentSite(), 'image');
        $groups     = $this->getGroupRepository()->getAllGroupsForUser($user);

        if ($request->getMethod() == 'POST')
        {
            $em = $this->getEntityManager();
            $form->bindRequest($request);
            $images = $form->getData();

            foreach ($images['galleryImages'] as $image)
            {
                $image->setOwner($user);

                $em->persist($image);

                $media = new GalleryMedia();
                $media->setImage($image);
                $media->setAuthor($user);
                $media->setCategory('image');
                $media->setTitle($image->getFileName());
                $em->persist($media);
            }

            $em->flush();

            $this->setFlash('success', $this->trans('galleries.submit_photo_upload_success'));
            return $this->redirect($this->generateUrl('gallery_submit'));
        }


        return $this->render('SpoutletBundle:Gallery:submit.html.twig', array(
            'form'      => $form->createView(),
            'medias'    => $medias,
            'galleries' => $galleries,
            'groups'    => $groups,
        ));
    }

    public function editPhotosAction()
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user       = $this->getCurrentUser();
        $medias     = $this->getGalleryMediaRepository()->findAllUnpublishedByUser($user);
        $galleries  = $this->getGalleryRepository()->findAllGalleriesByCategory('image');
        $groups     = $this->getGroupRepository()->getAllGroupsForUser($user);

        return $this->render('SpoutletBundle:Gallery:editPhotos.html.twig', array(
            'medias' => $medias,
            'galleries' => $galleries,
            'groups'    => $groups,
        ));
    }

    public function featureAction($id)
    {
        $em     = $this->getEntityManager();
        $media  = $this->getGalleryMediaRepository()->find($id);

        if (!$media->isAllowedTo($this->getCurrentUser(), $this->getCurrentSite(), 'FeatureMedia')) {
            $this->setFlash('error', 'Sorry, You are not allowed to do this.');
            return $this->redirect($this->generateUrl('gallery_media_show', array( 'id' => $id )));
        }

        $media->setFeatured(!$media->getFeatured());
        $em->persist($media);
        $em->flush();

        $flashString = $media->getFeatured() ? 'Media was featured successfully!' : 'Media was unfeatured successfully!';

        if($media->getFeatured()) {
            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('photofeature', $media->getAuthor()->getCevoUserId());
            } catch (ApiException $e) {

            }
        }

        $this->setFlash('success', $flashString);
        return $this->redirect($this->generateUrl('gallery_media_show', array( 'id' => $id )));
    }

    public function publishMultipleAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "message" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        $totalImages = $params['totalImages'];
        $medias      = $params['medias'];

        $em     = $this->getEntityManager();

        $unpublished = array();
        $published   = array();
        $allErrors   = array();

        foreach ($medias as $submission) {
            $id          = (int) $submission['id'];
            $title       = $submission['title'];
            $description = $submission['description'];
            $gals        = $submission['galleries'];
            $groups      = $submission['groups'];

            $errors      = $this->validateMediaPublish($id, $title, $description, $gals, $groups);

            $galleryIds  = count($gals) == 0 ? array(0) : $gals;
            $media       = $this->getGalleryMediaRepository()->find($id);
            $galleries   = $this->getGalleryRepository()->findAllGalleries($galleryIds);
            $groupRepo   = $this->getGroupRepository();
            $user        = $this->getCurrentUser();
            $site        = $this->getCurrentSite();

            $media->setTitle($title);
            $media->setDescription($description);
            $media->setGalleries($galleries);

            if(count($errors) == 0)
            {
                $published[] = $id;
                $media->setPublished(true);
                try {
                    $cevoResponse = $this->getCEVOApiManager()->GiveUserXp('photosubmit', $media->getAuthor()->getCevoUserId());
                } catch(ApiException $e) {

                }
            } else {
                $unpublished[] = $id;
                $allErrors[] = $errors;
            }

            $em->persist($media);

            if (count($groups) > 0) {
                foreach ($groups as $group) {

                    $group = $groupRepo->find($group);
                    if ($group && $this->getGroupManager()->isAllowedTo($user, $group, $site, 'AddImage')) {
                        $groupImage = new GroupImage();
                        $groupImage->setGroup($group);
                        $groupImage->setTitle($title);
                        $groupImage->setImage($media->getImage());
                        $groupImage->setAuthor($user);

                        $em->persist($groupImage);
                        try {
                            $cevoResponse = $this->getCEVOApiManager()->GiveUserXp('submitgroupphoto', $media->getAuthor()->getCevoUserId());
                        } catch(ApiException $e) {

                        }
                    }
                }
            }

            $em->flush();
        }

        $this->setFlash('success', sprintf($this->trans('galleries.publish_photo_multiple_message'), count($published), $totalImages));
        $message = sprintf($this->trans('galleries.publish_photo_multiple_message'), count($published), $totalImages);

        $response->setContent(json_encode(array(
            "success" => true,
            "message" => $message,
            "published" => $published,
            "unpublished" => $unpublished,
            "errors" => $allErrors)
        ));

        return $response;
    }

    public function publishAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "message" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['id']) || !isset($params['title']) || !isset($params['description']) || !isset($params['galleries']) || !isset($params['groups'])) {

            $response->setContent(json_encode(array(
                "success" => false,
                "message" => "Some required information was not passed."
            )));
            return $response;
        }

        $id          = (int) $params['id'];
        $title       = $params['title'];
        $description = $params['description'];
        $gals        = $params['galleries'];
        $groups      = $params['groups'];

        $errors = $this->validateMediaPublish($id, $title, $description, $gals, $groups);

        $canPublish = count($errors) == 0;

        if(count($errors) > 0)
        {
            $response->setContent(json_encode(array(
                "success" => false,
                "message" => $this->trans('galleries.publish_photo_error'),
                "errors"  => $errors
            )));
            return $response;
        }

        $galleryIds = count($gals) == 0 ? array(0) : $gals;

        $galleries  = $this->getGalleryRepository()->findAllGalleries($galleryIds);
        $media      = $this->getGalleryMediaRepository()->find($id);
        $groupRepo  = $this->getGroupRepository();
        $user       = $this->getCurrentUser();
        $site       = $this->getCurrentSite();
        $em         = $this->getEntityManager();

        if(!$media)
        {
            $response->setContent(json_encode(array("success" => false, "message" => "Unable to find photo.")));
            return $response;
        }

        $media->setTitle($title);
        $media->setDescription($description);

        if($canPublish)
        {
            $media->setPublished(true);
        }

        $media->setGalleries($galleries);

        $em->persist($media);

        if (count($groups) > 0) {
            foreach ($groups as $group) {

                $group      = $groupRepo->find($group);
                if ($group && $this->getGroupManager()->isAllowedTo($user, $group, $site, 'AddImage')) {
                    $groupImage = new GroupImage();
                    $groupImage->setGroup($group);
                    $groupImage->setTitle($title);
                    $groupImage->setImage($media->getImage());
                    $groupImage->setAuthor($user);

                    $em->persist($groupImage);
                }
            }
        }

        $em->flush();

        $response->setContent(json_encode(array("success" => true, 'message' => 'Photo published successfully')));
        return $response;
    }

    private function validateMediaPublish($id, $title, $desc, $galleries, $groups)
    {
        $errors = array();
        if($id == 0)
        {
            $errors[] = $this->trans('galleries.publish_photo_error_id');
        }

        if(StringUtil::IsNullOrEmptyString($title))
        {
            $errors[] = 'title';
        }

        if(StringUtil::IsNullOrEmptyString($desc))
        {
            $errors[] ='description';
        }

        if(count($galleries) == 0 && count($groups) == 0)
        {
            $errors[] = 'galleries';
        }

        return $errors;
    }

    public function showAction($id, Request $request)
    {
        $media          = $this->getGalleryMediaRepository()->find($id);

        if(!$media)
        {
            throw $this->createNotFoundException('No media found.');
        }

        $site = $this->getCurrentSite();

        if($media->getDeleted() || !$media->isVisibleOnSite($site) || !$media->getPublished())
        {
            throw $this->createNotFoundException('No media found.');
        }

        $mediaId = (int)$request->query->get('vote');

        if ($mediaId && $this->isGranted('ROLE_USER')) {

            if ($media && !$media->hasUserVoted($this->getUser())) {
                $vote = new Vote();
                $vote->setUser($this->getUser());
                $vote->setGalleryMedia($media);
                $vote->setVoteType('up');
                $vote->setIpAddress($request->getClientIp(true));

                $em = $this->getEntityManager();

                $em->persist($vote);

                $media->getVotes()->add($vote);
                $em->persist($media);
                $em->flush();
            }
        }

        $otherMedia     = $this->getGalleryMediaRepository()->findAllPublishedByUserNewestFirstExcept($media->getAuthor(), $id, $site);

        $otherMediaPerPage = 3;
        $pageCount = ceil(count($otherMedia) / $otherMediaPerPage);

        $otherMediaPages = array();
        $offset = 0;
        for($i = 0; $i < $pageCount; $i++)
        {
            $otherMediaPages[] = array_slice($otherMedia, $offset, $otherMediaPerPage);
            $offset += $otherMediaPerPage;
        }

        $voteRepo       = $this->getVoteRepository();
        $likes        = $voteRepo->findUpVotes($id);

        $views = $media->getViews();

        $views++;

        $media->setViews($views);

        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();

        if ($request->headers->get('referer') != null) {
            $referer = parse_url($request->headers->get('referer'));
            $pathArr = explode('/', $referer['path']);

            if ($pathArr[1] == "app_dev.php" || $pathArr[1] == "app_test.php") {
                $returnType = $pathArr[2];
            } else {
                $returnType = $pathArr[1];
            }

            if ($returnType == "contests") {

                $contest = $media->getContestEntry() ? $media->getContestEntry()->getContest() : null;
                $crumb = $contest ? array('value' => $request->headers->get('referer'), 'text' => $contest->getName()) : null;

            } else {
                $crumb = $this->getGalleryBreadCrumb($request->headers->get('referer'), $media->getGalleries());
            }
        } else {
            $returnType = "galleries";
            $crumb = null;
        }

        return $this->render('SpoutletBundle:Gallery:show.html.twig', array(
            'media'             => $media,
            'otherMediaPages'   => $otherMediaPages,
            'likes'             => $likes,
            'crumb'             => $crumb,
            'returnType'        => $returnType,
        ));
    }

    public function editMediaAction($id, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user   = $this->getCurrentUser();
        $media  = $this->getGalleryMediaRepository()->find($id);

        if(!$media)
        {
            throw $this->createNotFoundException('Media not found.');
        }

        $form = $this->createForm(new GalleryMediaType($user), $media);

        if($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);

            if($form->isValid())
            {
                $em = $this->getEntityManager();
                $media = $form->getData();

                if(count($media->getGalleries()) == 0)
                {
                    $form = $this->createForm(new GalleryMediaType($user), $media);
                    $this->setFlash('error', $this->trans('galleries.publish_photo_error_gallery'));

                    return $this->render('SpoutletBundle:Gallery:edit.html.twig', array(
                        'media' => $media,
                        'form'  => $form->createView(),
                    ));
                }

                $em->persist($media);
                $em->flush();

                $this->setFlash('success', 'Your changes are saved.');
                return $this->redirect($this->generateUrl('gallery_media_show', array('id' => $media->getId())));
            }
        }

        return $this->render('SpoutletBundle:Gallery:edit.html.twig', array(
            'media' => $media,
            'form'  => $form->createView(),
        ));
    }

    public function deleteMediaAction($id, Request $request)
    {
        $user = $this->getCurrentUser();

        $media = $this->getGalleryMediaRepository()->find($id);

        if(!$media)
        {
            throw $this->createNotFoundException('Media not found');
        }

        if(($media->getAuthor()->getId() != $user->getId()) && !$user->hasRole('ROLE_SUPER_ADMIN'))
        {
            $this->setFlash('error', $this->trans('galleries.delete_photo_error_message'));
            return $this->redirect($this->generateUrl('gallery_media_show', array('id' => $id)));
        }

        $em = $this->getEntityManager();
        $media->setDeleted(true);
        $em->persist($media);
        $em->flush();

        $this->setFlash('success', $this->trans('galleries.delete_photo_success_message'));
        return $this->redirect($this->generateUrl('gallery_index'));
    }

    public function deleteMediaAjaxAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();
        $params = json_decode($content, true);

        if (empty($content) || !isset($params['id'])) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $id                 = $params['id'];
        $galleryMediaRepo   = $this->getGalleryMediaRepository();
        $media              = $galleryMediaRepo->find($id);

        $user = $this->getCurrentUser();

        if(!$media || $media->getAuthor()->getId() != $user->getId())
        {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $em = $this->getEntityManager();
        $media->setDeleted(true);
        $em->persist($media);
        $em->flush();

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    public function voteAction(Request $request)
    {
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
        $vote       = new Vote();
        $user       = $this->getCurrentUser();

        if (!$this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => 'FORCE_LOGIN_TO_VOTE')));
            return $response;
        }

        if (!in_array($voteType, $vote->getValidVoteTypes())) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Valid vote type (up/down) not given.")));
            return $response;
        }

        $galleryMediaRepo   = $this->getGalleryMediaRepository();
        $contestRepo        = $this->getContestRepository();
        $voteRepo           = $this->getVoteRepository();
        $countryRepo        = $this->getCountryRepository();

        $media              = $galleryMediaRepo->find($id);

        $contest            = $media->getContestEntry() ? $media->getContestEntry()->getContest() : null;
        $country            = $countryRepo->findOneByCode($user->getCountry());

        if ($contest && !$contest->isFinished() && !$contest->getRuleset()->doesUserPassRules($user, $country)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "You are not eligible to vote on this contest")));
            return $response;
        }


        if ($contest && !$contestRepo->canUserVoteBasedOnSite($user, $contest)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "This contest is not enabled for your region.")));
            return $response;
        }

        if (!$voteRepo->canVoteOnMedia($media, $user)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "You have already voted on this item.")));
            return $response;
        }

        $vote->setUser($user);
        $vote->setGalleryMedia($media);
        $vote->setVoteType($voteType);
        $vote->setIpAddress($request->getClientIp(true));

        $em = $this->getEntityManager();

        $em->persist($vote);
        $em->flush();

        $likes = $this->getVoteRepository()->findUpVotes($media);

        $response->setContent(json_encode(array("success" => true, "messageForUser" => 'Vote successful', "likes" => $likes)));
        return $response;
    }

    public function sharePhotoAction()
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user           = $this->getCurrentUser();
        $repo           = $this->getGalleryMediaRepository();
        $groupImageRepo = $this->getGroupImageRepository();

        $media      = $repo->findMostRecentPublishedByUser($user);
        $groupMedia = $groupImageRepo->findMostRecentGroupImageForUser($user);

        if(!$media)
        {
            throw $this->createNotFoundException('No media found.');
        }

        return $this->render('SpoutletBundle:Gallery:share.html.twig', array(
            'media' => $media,
            'groupMedia' => $groupMedia,
        ));
    }

    public function galleryAction($slug, $sort='latest', Request $request)
    {
        $gallery    = $this->getGalleryRepository()->findOneBySlug($slug);
        $user       = $this->getUser();
        $mediaId    = (int)$request->query->get('vote');

        if(!$gallery || $gallery->getDeleted() || !$gallery->isVisibleOnSite($this->getCurrentSite()))
        {
            throw $this->createNotFoundException('Gallery not found.');
        }

        if ($mediaId && $this->isGranted('ROLE_USER')) {
            $media = $this->getGalleryMediaRepository()->find($mediaId);

            if ($media && !$media->hasUserVoted($this->getUser())) {
                $vote = new Vote();
                $vote->setUser($this->getUser());
                $vote->setGalleryMedia($media);
                $vote->setVoteType('up');
                $vote->setIpAddress($request->getClientIp(true));

                $em = $this->getEntityManager();

                $em->persist($vote);

                $media->getVotes()->add($vote);
                $em->persist($media);
                $em->flush();
            }
        }

        $page = 0;
        $returnId = null;

        $referer = $request->headers->get('referer');

        if ($referer) {
            $parts = explode('/', $referer);
            $parsedUrl = parse_url($referer);

            if (is_numeric(end($parts)) && strpos($parsedUrl['path'], 'galleries/photo')) {
                $returnId = end($parts);
            }
        }

        if($sort == 'popular')
        {
            $medias = $this->getGalleryMediaRepository()->findTopMediaForGallery($gallery, 100);
        } else {
            $medias = $this->getGalleryMediaRepository()->findLatestMediaForGallery($gallery, 100);
        }

        if ($returnId) {
            foreach ($medias as $key => $media) {
                if ($media->getId() == $returnId) {
                    $page = floor($key/16);
                }
            }
        }

        return $this->render('SpoutletBundle:Gallery:gallery.html.twig', array(
            'gallery'       => $gallery,
            'medias'        => $medias,
            'sort'          => $sort,
            'page'          => $page,
        ));
    }

    public function galleryDataAction(Request $request)
    {
        $type = $request->get('type');
        $repo = $this->getGalleryMediaRepository();
        $site = $this->getCurrentSite();

        switch ($type) {
            case 'featured':
                # get featured media
                $medias = $repo->findFeaturedMediaForSite($site);
                return $this->render('SpoutletBundle:Gallery:_media.html.twig', array(
                    'medias' => $medias,
                    'type'   => $type,
                ));

            case 'latest':
                # get latest media
                $medias = $repo->findLatestMediaForSite($site);
                return $this->render('SpoutletBundle:Gallery:_media.html.twig', array(
                    'medias' => $medias,
                    'type'   => $type,
                ));

            case 'popular':
                # get popular media (based on views but will need to be based on ratings when those are implemented)
                $medias = $repo->findPopularMediaForSite($site);

                return $this->render('SpoutletBundle:Gallery:_media.html.twig', array(
                    'medias' => $medias,
                    'type'   => $type,
                ));

            default:
                # if its none of the above, we'll assume its a gallery id
                $medias = $repo->findMediaForGalleryByGalleryId($type);
                return $this->render('SpoutletBundle:Gallery:_media.html.twig', array(
                    'medias' => $medias,
                    'type'   => $type,
                ));
        }
    }

    private function getFilterOptions()
    {
        $filterOptions  = array();
        $site           = $this->getCurrentSite();
        $galleries      = $this->getGalleryRepository()->findAllGalleriesForSite($site);

        $filterOptions[] = array(
            'value' => 'featured',
            'name'  => 'galleries.gallery_filter_option_featured',
            'slug'  => ''
        );
        $filterOptions[] = array(
            'value' => 'latest',
            'name'  => 'galleries.gallery_filter_option_latest',
            'slug'  => ''
        );
        $filterOptions[] = array(
            'value' => 'popular',
            'name'  => 'galleries.gallery_filter_option_popular',
            'slug'  => ''
        );

        foreach ($galleries as $gallery) {
            $filterOptions[] = array(
                'value' => $gallery->getId(),
                'name'  => $gallery->getName(),
                'slug'  => $gallery->getSlug()
            );
        }

        return $filterOptions;
    }

    public function featuredMediaFeedAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $content  = $request->getContent();

        $subdomain = $request->request->get('_site');

        if (!$subdomain) {
            $response->setContent(json_encode(array("error" => "Site not specified.")));
            return $response;
        }

        $em         = $this->getEntityManager();

        $siteRepo           = $em->getRepository('SpoutletBundle:Site');
        $galleryMediaRepo   = $em->getRepository('SpoutletBundle:GalleryMedia');

        foreach ($siteRepo->findAll() as $dbSite) {
            if ($dbSite->getSubDomain() == $subdomain) {
                $site = $dbSite;
                break;
            }
        }

        if (!$site) {
            $response->setContent(json_encode(array("error" => "Invalid site specified.")));
            return $response;
        }

        $media  = $galleryMediaRepo->findFeaturedMediaForSite($site);

        $featuredMedia = array();

        if ($media) {
            $counter = 0;
            $liip = $this->get('liip_imagine.templating.helper');
            foreach($media as $mediaItem) {
                $featuredMedia[$counter]['thumbnail']   = $liip->filter($mediaItem->getImage()->getFilename(), 'media_feed_thumbnail', true);
                $featuredMedia[$counter]['url']         = $this->generateUrl('gallery_media_show', array('id' => $mediaItem->getId(), '_locale' => $site->getDefaultLocale()), true);
                $counter++;
            }
        } else {
            $featuredMedia = null;
        }

        $response->setContent(json_encode(array(
            "success" => true,
            "media"   => $featuredMedia
        )));

        return $response;
    }

    private function getEntityManager()
    {
        return $this->getDoctrine()->getEntityManager();
    }

    private function getGalleryMediaRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:GalleryMedia');
    }

    private function getGalleryRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Gallery');
    }

    private function getGroupRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:Group');
    }

    private function getGroupImageRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:GroupImage');
    }

    private function getContestRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Contest');
    }

    private function getVoteRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Vote');
    }

    private function getCountryRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Country');
    }

    private function getGalleryBreadCrumb($previousUrl, $galleries)
    {
        $breadCrumb = array();
        foreach ($galleries as $gallery) {
            if(strpos($previousUrl, $gallery->getSlug()) !== false) {
                $breadCrumb['value'] = $previousUrl;
                $breadCrumb['text'] = $gallery->getName();
            }
        }
        return $breadCrumb;
    }

    private function getCurrentUser()
    {
        return $this->get('security.context')->getToken()->getUser();
    }

    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }

    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }
}
