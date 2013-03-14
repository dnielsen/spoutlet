<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Wallpaper;
use Platformd\SpoutletBundle\Form\Type\WallpaperType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Wallpaper admin controller.
 *
 */
class WallpaperAdminController extends Controller
{
    /**
     * Lists all Wallpaper entities.
     *
     */
    public function indexAction()
    {
        $this->addWallpapersBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('SpoutletBundle:Wallpaper')->findAllNewestFirst();

        return $this->render('SpoutletBundle:WallpaperAdmin:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Creates a new Wallpaper wallpaper.
     *
     */
    public function newAction(Request $request)
    {
        $this->addWallpapersBreadcrumb()->addChild('New Wallpaper');

        $wallpaper  = new Wallpaper();
        $form    = $this->createForm(new WallpaperType(), $wallpaper);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The wallpaper was created!');

            return $this->redirect($this->generateUrl('admin_wallpaper_edit', array('id' => $wallpaper->getId())));
        }

        return $this->render('SpoutletBundle:WallpaperAdmin:new.html.twig', array(
            'entity' => $wallpaper,
            'form'   => $form->createView()
        ));
    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $wallpaper = $em
            ->getRepository('SpoutletBundle:Wallpaper')
            ->findOneBy(array('id' => $id));

        if (!$wallpaper) {
            throw $this->createNotFoundException('Unable to retrieve wallpaper #'.$id);
        }

        $em->remove($wallpaper);
        $em->flush();

        $this
            ->getRequest()
            ->getSession()
            ->setFlash('success', "The wallpaper was deleted!");

        return $this->redirect($this->generateUrl('admin_wallpaper'));
    }

    /**
     * Edits an existing Wallpaper wallpaper.
     *
     */
    public function editAction($id, Request $request)
    {
        $this->addWallpapersBreadcrumb()->addChild('Edit Wallpaper');
        $em = $this->getDoctrine()->getEntityManager();

        $wallpaper = $em->getRepository('SpoutletBundle:Wallpaper')->find($id);

        if (!$wallpaper) {
            throw $this->createNotFoundException('Unable to find Wallpaper wallpaper.');
        }

        $editForm   = $this->createForm(new WallpaperType(), $wallpaper);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The wallpaper was saved!');

            return $this->redirect($this->generateUrl('admin_wallpaper_edit', array('id' => $id)));
        }

        return $this->render('SpoutletBundle:WallpaperAdmin:edit.html.twig', array(
            'wallpaper'      => $wallpaper,
            'edit_form'   => $editForm->createView(),
        ));
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {

            $form->bindRequest($request);

            if ($form->isValid()) {
                /** @var $wallpaper \Platformd\SpoutletBundle\Entity\Wallpaper */
                $wallpaper = $form->getData();
                $em->persist($wallpaper);

                $mUtil = $this->getMediaUtil();

                // either persist the logo, or unset it
                if (!$mUtil->persistRelatedMedia($wallpaper->getThumbnail())) {
                    $wallpaper->setThumbnail(null);
                }

                // either persist the logo thumbnail, or unset it
                if (!$mUtil->persistRelatedMedia($wallpaper->getResolutionPack())) {
                    $wallpaper->setResolutionPack(null);
                }

                $em->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addWallpapersBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Wallpapers', array(
            'route' => 'admin_wallpaper'
        ));

        return $this->getBreadcrumbs();
    }
}
