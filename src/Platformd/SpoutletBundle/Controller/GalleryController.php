<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\MediaGallery;
use Platformd\SpoutletBundle\Entity\GalleryMedia;
use Platformd\SpoutletBundle\Form\Type\SubmitImageType;
use Platformd\SpoutletBundle\Form\Type\GalleryChoiceType;
use Platformd\SpoutletBundle\Form\Type\GalleryMediaType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Gallery controller.
 *
 */
class GalleryController extends Controller
{
    public function indexAction()
    {
        $medias = $this->getGalleryMediaRepository()->findMediaForIndexPage();
        return $this->render('SpoutletBundle:Gallery:index.html.twig', array(
            'medias' => $medias,
        ));
    }

    public function submitAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $form = $this->createForm(new SubmitImageType($user));

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

            $this->setFlash('success', 'Your images were uploaded successfully.');
            return $this->redirect($this->generateUrl('gallery_edit_photos'));
        }


        return $this->render('SpoutletBundle:Gallery:submit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function editPhotosAction()
    {
        $user = $this->getCurrentUser();
        $medias = $this->getGalleryMediaRepository()->findAllUnpublishedByUser($user);
        $galleries = $this->getGalleryRepository()->findAllGalleriesByCategory('image');

        return $this->render('SpoutletBundle:Gallery:editPhotos.html.twig', array(
            'medias' => $medias,
            'galleries' => $galleries
        ));
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

        if (!isset($params['id']) || !isset($params['title']) || !isset($params['description']) || !isset($params['galleries'])) {
            $response->setContent(json_encode(array("success" => false, "message" => "Some required information was not passed.")));
            return $response;
        }

        $id          = (int) $params['id'];
        $title       = $params['title'];
        $description = $params['description'];
        $gals        = $params['galleries'];

        $galleries = $this->getGalleryRepository()->findAllGalleries($gals);

        $media = $this->getGalleryMediaRepository()->find($id);

        if(!$media)
        {
            $response->setContent(json_encode(array("success" => false, "message" => "Unable to find photo.")));
            return $response;
        }

        $media->setTitle($title);
        $media->setDescription($description);
        $media->setPublished(true);
        $media->setGalleries($galleries);

        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();

        $response->setContent(json_encode(array("success" => true, 'message' => 'Photo published successfully')));
        return $response;
    }

    public function showAction($id)
    {
        $media = $this->getGalleryMediaRepository()->find($id);

        if(!$media)
        {
            throw $this->createNotFoundException('No media found.');
        }

        $views = $media->getViews();
        $views += 1;
        $media->setViews($views);

        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();

        return $this->render('SpoutletBundle:Gallery:show.html.twig', array(
            'media' => $media,
        ));
    }

    public function galleryAction($slug)
    {
        $repo = $this->getGalleryRepository();

        $gallery = $repo->findOneBySlug($slug);

        if(!$gallery)
        {
            throw $this->createNotFoundException('Gallery not found.');
        }

        return $this->render('SpoutletBundle:Gallery:gallery.html.twig', array(
            'gallery' => $gallery,
        ));
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

    private function getCurrentUser()
    {
        return $this->get('security.context')->getToken()->getUser();
    }
}
