<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\MediaGallery;

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
        return $this->render('SpoutletBundle:Gallery:submit.html.twig');
    }
}
