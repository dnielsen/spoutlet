<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\MediaBundle\Entity\Media,
    Platformd\MediaBundle\Form\Type\MediaType
;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Admin controller for events
 */
class AdminController extends Controller
{
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Admin:index.html.twig');
    }

    public function manageMediaAction(Request $request)
    {
        $this->addManageMediaBreadcrumb();

        $page   = $request->query->get('page', 1);
        $pager  = $this->getMediaRepo()->getMediaForAdmin(50, $page);
        $medias = $pager->getCurrentPageResults();

        $media  = new Media();
        $form   = $this->createForm(new MediaType(), $media);

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if($form->isValid()) {

                $this->saveMedia($form);
                return $this->redirect($this->generateUrl('admin_upload_media'));
            }
        }

        return $this->render('SpoutletBundle:Admin:manageMedia.html.twig', array(
            'medias' => $medias,
            'pager'   => $pager,
            'form'   => $form->createView(),
        ));
    }

    private function saveMedia($mediaForm)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $media = $mediaForm->getData();
        $media->setOwner($this->getUser());
        $media->setIsAdmin(true);
        $em->persist($media);
        $em->flush();

        $this->setFlash('success', 'Your media was uploaded succesfully');
    }

    private function getMediaRepo()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('MediaBundle:Media');
    }

    private function addManageMediaBreadcrumb()
    {
         $this->getBreadcrumbs()->addChild('Manage Media', array(
            'route' => 'admin_upload_media'
        ));

        return $this->getBreadcrumbs();
    }
}
