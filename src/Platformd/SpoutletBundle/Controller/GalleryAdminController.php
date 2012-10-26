<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Gallery;
use Platformd\SpoutletBundle\Entity\GalleryRepository;
use Platformd\SpoutletBundle\Form\Type\GalleryType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;

/**
 * Gallery admin controller.
 *
 */
class GalleryAdminController extends Controller
{
    public function indexAction()
    {
        $this->addGalleriesBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $galleries = $em->getRepository('SpoutletBundle:Gallery')->findAllAlphabetically();

        return $this->render('SpoutletBundle:GalleryAdmin:index.html.twig', array(
            'galleries' => $galleries
        ));
    }

    public function newAction(Request $request)
    {
        $this->addGalleriesBreadcrumb()->addChild('New Gallery');

        $gallery  = new Gallery();
        $form    = $this->createForm(new GalleryType(), $gallery);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The gallery was created!');

            return $this->redirect($this->generateUrl('admin_gallery_index'));
        }

        return $this->render('SpoutletBundle:GalleryAdmin:new.html.twig', array(
            'gallery' => $gallery,
            'form'   => $form->createView()
        ));
    }

    public function editAction($slug, Request $request)
    {
        $this->addGalleriesBreadcrumb()->addChild('Edit Gallery');
        $em = $this->getDoctrine()->getEntityManager();

        $gallery = $em->getRepository('SpoutletBundle:Gallery')->findOneBy(array('slug' => $slug));

        if (!$gallery) {
            throw $this->createNotFoundException('Unable to find gallery.');
        }

        $editForm   = $this->createForm(new GalleryType(), $gallery);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The gallery was saved!');

            return $this->redirect($this->generateUrl('admin_gallery_index'));
        }

        return $this->render('SpoutletBundle:GalleryAdmin:edit.html.twig', array(
            'gallery'      => $gallery,
            'edit_form'   => $editForm->createView(),
        ));
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $gallery = $form->getData();

                if (!$gallery->getOwner()) {
                    $gallery->setOwner($this->getUser());
                }

                $em->persist($gallery);

                $em->flush();

                return true;
            }
        }

        return false;
    }

    private function addGalleriesBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Galleries', array(
            'route' => 'admin_gallery_index'
        ));

        return $this->getBreadcrumbs();
    }
}
