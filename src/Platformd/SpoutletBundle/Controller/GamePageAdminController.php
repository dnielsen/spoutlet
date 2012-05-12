<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\GamePage;
use Platformd\SpoutletBundle\Form\Type\GamePageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * GamePage admin controller.
 *
 */
class GamePageAdminController extends Controller
{
    /**
     * Lists all GamePage entities.
     *
     */
    public function indexAction()
    {
        $this->addGamePagesBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('SpoutletBundle:GamePage')->findAll();

        return $this->render('SpoutletBundle:GamePageAdmin:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Creates a new GamePage gamePage.
     *
     */
    public function newAction(Request $request)
    {
        $this->addGamePagesBreadcrumb()->addChild('New GamePage');

        $gamePage  = new GamePage();
        $form    = $this->createForm(new GamePageType(), $gamePage);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The gamePage was created!');

            return $this->redirect($this->generateUrl('admin_game_page_edit', array('id' => $gamePage->getId())));
        }

        return $this->render('SpoutletBundle:GamePageAdmin:new.html.twig', array(
            'entity' => $gamePage,
            'form'   => $form->createView()
        ));
    }

    /**
     * Edits an existing GamePage gamePage.
     *
     */
    public function editAction($id, Request $request)
    {
        $this->addGamePagesBreadcrumb()->addChild('Edit GamePage');
        $em = $this->getDoctrine()->getEntityManager();

        $gamePage = $em->getRepository('SpoutletBundle:GamePage')->find($id);

        if (!$gamePage) {
            throw $this->createNotFoundException('Unable to find GamePage gamePage.');
        }

        $editForm   = $this->createForm(new GamePageType(), $gamePage);
        $deleteForm = $this->createDeleteForm($id);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The gamePage was saved!');

            return $this->redirect($this->generateUrl('admin_game_page_edit', array('id' => $id)));
        }

        return $this->render('SpoutletBundle:GamePageAdmin:edit.html.twig', array(
            'gamePage'      => $gamePage,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                /** @var $gamePage \Platformd\SpoutletBundle\Entity\GamePage */
                $gamePage = $form->getData();
                $em->persist($gamePage);

                // either persist the logo, or unset it
                if ($gamePage->getLogo()->getFileObject()) {
                    $em->persist($gamePage->getLogo());
                } else {
                    $gamePage->setLogo(null);
                }

                // either persist the logos, or unset it
                if ($gamePage->getPublisherLogos()->getFileObject()) {
                    $em->persist($gamePage->getPublisherLogos());
                } else {
                    $gamePage->setPublisherLogos(null);
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
    private function addGamePagesBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('GamePages', array(
            'route' => 'admin_game_page'
        ));

        return $this->getBreadcrumbs();
    }
}
