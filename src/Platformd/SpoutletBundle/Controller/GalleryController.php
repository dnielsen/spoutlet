<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\MediaGallery;
use Platformd\SpoutletBundle\Entity\GalleryImage;
use Platformd\SpoutletBundle\Form\Type\SubmitImageType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\HttpFoundation\Request;
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
        return $this->render('SpoutletBundle:Gallery:index.html.twig');
    }

    public function submitAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $form = $this->createForm(new SubmitImageType($user));

        if ($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);
            $images = $form->getData();

            foreach ($images as $image)
            {
                $media = new GalleryMedia();
                $media->setImage($image);
                $media->setOwner($user);
                $media->setCategory('image');
            }

            $this->setFlash('success', 'Your images were uploaded successfully.');
            return $this->redirect('');
        }


        return $this->render('SpoutletBundle:Gallery:submit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function editPhotosAction()
    {
        return null;
    }

    private function getEntityManager()
    {
        return $this->getDoctrine()->getEntityManager();
    }

    private function getCurrentUser()
    {
        return $this->get('security.context')->getToken()->getUser();
    }
}
