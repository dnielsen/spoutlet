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

    public function submitAction()
    {
        $user = $this->getCurrentUser();

        $form = $this->createFormBuilder()
            ->add('title', 'text')
            ->add('galleryImages', 'collection', array(
                'allow_add'     => true,
                'allow_delete'  => true,
                'type'          => new MediaType(),
                'options'       => array(
                    'image_label' => 'Gallery Image',
                    'image_help'  => 'Only jpg, png and gif images allowed',
                )
            ))
            ->getForm();

        return $this->render('SpoutletBundle:Gallery:submit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }
}
