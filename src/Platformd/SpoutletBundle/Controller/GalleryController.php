<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\MediaGallery;
use Platformd\SpoutletBundle\Entity\GalleryMedia;
use Platformd\SpoutletBundle\Entity\GroupImage;
use Platformd\SpoutletBundle\Entity\Vote;
use Platformd\SpoutletBundle\Form\Type\SubmitImageType;
use Platformd\SpoutletBundle\Form\Type\GalleryChoiceType;
use Platformd\SpoutletBundle\Form\Type\GalleryMediaType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Platformd\SpoutletBundle\Util\StringUtil;
use Platformd\UserBundle\Entity\User;

/**
 * Gallery controller.
 *
 */
class GalleryController extends Controller
{
    public function indexAction()
    {
        $nivoSliderMedia = $this->getGalleryMediaRepository()->findFeaturedMedia(5);
        $options = $this->getFilterOptions();

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

        $this->setFlash('success', $flashString);
        return $this->redirect($this->generateUrl('gallery_media_show', array( 'id' => $id )));
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

        $errors = $this->validateMediaPublish($id, $title, $description, $gals);

        if(count($errors) > 0)
        {
            $response->setContent(json_encode(array(
                "success" => false,
                "message" => $this->trans('galleries.publish_photo_error'),
                "errors"  => $errors
            )));
            return $response;
        }

        $galleries  = $this->getGalleryRepository()->findAllGalleries($gals);
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
        $media->setPublished(true);
        $media->setGalleries($galleries);

        $em->persist($media);

        if (count($groups) > 0) {
            foreach ($groups as $group) {

                $group      = $groupRepo->find($group);
                if ($group && $group->isAllowedTo($user, $site, 'AddImage')) {
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

    private function validateMediaPublish($id, $title, $desc, $galleries)
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

        if(count($galleries) == 0)
        {
            $errors[] = 'galleries';
        }

        return $errors;
    }

    public function showAction($id, Request $request)
    {
        $media          = $this->getGalleryMediaRepository()->find($id);

        $otherMedia     = $this->getGalleryMediaRepository()->findAllPublishedByUserNewestFirstExcept($media->getAuthor(), $id);

        $totalVotes     = $this->getVoteRepository()->findVoteCount($id);
        $upVotes        = round(($this->getVoteRepository()->findUpVotes($id)/$totalVotes)*100);
        $downVotes      = 100 - $upVotes;

        if(!$media)
        {
            throw $this->createNotFoundException('No media found.');
        }

        $views = $media->getViews();

        $views++;

        $media->setViews($views);

        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();

        $crumb = $this->getGalleryBreadCrumb($request->headers->get('referer'), $media->getGalleries());

        return $this->render('SpoutletBundle:Gallery:show.html.twig', array(
            'media'         => $media,
            'otherMedia'    => $otherMedia,
            'upVotes'       => $upVotes,
            'crumb'         => $crumb,
            'downVotes'     => $downVotes,
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
                $em->persist($media);
                $em->flush();

                $this->setFlash('success', 'Your changes are saved.');
                return $this->redirect($this->generateUrl('gallery_edit_media', array('id' => $media->getId())));
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

        if($media->getAuthor()->getId() != $user->getId())
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

        if ($contest && !$contest->getRuleset()->doesUserPassRules($user, $country)) {
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

        $totalVotes     = $this->getVoteRepository()->findVoteCount($media);
        $upVotes        = round(($this->getVoteRepository()->findUpVotes($media)/$totalVotes)*100);

        $response->setContent(json_encode(array("success" => true, "messageForUser" => $upVotes)));
        return $response;
    }

    public function sharePhotoAction()
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user = $this->getCurrentUser();
        $repo = $this->getGalleryMediaRepository();

        $media = $repo->findMostRecentPublishedByUser($user);

        if(!$media)
        {
            throw $this->createNotFoundException('No media found.');
        }

        return $this->render('SpoutletBundle:Gallery:share.html.twig', array(
            'media' => $media,
        ));
    }

    public function galleryAction($slug, $sort='latest', Request $request)
    {
        $gallery = $this->getGalleryRepository()->findOneBySlug($slug);

        if(!$gallery || $gallery->getDeleted())
        {
            throw $this->createNotFoundException('Gallery not found.');
        }

        if($sort == 'popular')
        {
            $medias = $this->getGalleryMediaRepository()->findTopMediaForGallery($gallery, 100);
        } else {
            $medias = $this->getGalleryMediaRepository()->findLatestMediaForGallery($gallery, 100);
        }

        return $this->render('SpoutletBundle:Gallery:gallery.html.twig', array(
            'gallery'       => $gallery,
            'medias'        => $medias,
            'sort'          => $sort,
        ));
    }

    public function galleryDataAction(Request $request)
    {
        $type = $request->get('type');
        $repo = $this->getGalleryMediaRepository();

        switch ($type) {
            case 'featured':
                # get featured media
                $medias = $repo->findFeaturedMedia();
                return $this->render('SpoutletBundle:Gallery:_media.html.twig', array(
                    'medias' => $medias,
                    'type'   => $type,
                ));

            case 'latest':
                # get latest media
                $medias = $repo->findLatestMedia();
                return $this->render('SpoutletBundle:Gallery:_media.html.twig', array(
                    'medias' => $medias,
                    'type'   => $type,
                ));

            case 'popular':
                # get popular media (based on views but will need to be based on ratings when those are implemented)
                $medias = $repo->findPopularMedia();

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
        return $this->getEntityManager()->getRepository('SpoutletBundle:Group');
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
}
