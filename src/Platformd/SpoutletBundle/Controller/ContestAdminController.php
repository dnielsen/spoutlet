<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\SpoutletBundle\Entity\ContestRepository;
use Platformd\SpoutletBundle\Form\Type\ContestType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Knp\MediaBundle\Util\MediaUtil;

class ContestAdminController extends Controller
{
    public function indexAction()
    {
        $this->addContestsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $contests = $em->getRepository('SpoutletBundle:Contest')->findAllAlphabetically();

        return $this->render('SpoutletBundle:ContestAdmin:index.html.twig', array(
            'contests' => $contests
        ));
    }

    public function newAction(Request $request)
    {
        $this->addContestsBreadcrumb()->addChild('New Contest');

        $contest  = new Contest();
        $form    = $this->createForm(new ContestType(), $contest);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The contest was created!');

            return $this->redirect($this->generateUrl('admin_contest_index'));
        }

        return $this->render('SpoutletBundle:ContestAdmin:new.html.twig', array(
            'contest' => $contest,
            'form'   => $form->createView()
        ));
    }

    /*public function editAction($slug, Request $request)
    {
        $this->addContestsBreadcrumb()->addChild('Edit Contest');
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
    }*/

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $contest = $form->getData();

                $mUtil = new MediaUtil($this->getDoctrine()->getEntityManager());

                if (!$mUtil->persistRelatedMedia($contest->getBanner())) {
                    $contest->setBanner(null);
                }

                $em->persist($contest);

                $em->flush();

                return true;
            }
        }

        return false;
    }

    private function addContestsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Contests', array(
            'route' => 'admin_contest_index'
        ));

        return $this->getBreadcrumbs();
    }
}
