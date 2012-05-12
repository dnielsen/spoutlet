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
        $this->addGamePagesBreadcrumb()->addChild('New Game Page');

        $gamePage  = new GamePage();
        $form    = $this->createForm(new GamePageType(), $gamePage);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The game page was created!');

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
        $this->addGamePagesBreadcrumb()->addChild('Edit Game Page');
        $em = $this->getDoctrine()->getEntityManager();

        $gamePage = $em->getRepository('SpoutletBundle:GamePage')->find($id);

        if (!$gamePage) {
            throw $this->createNotFoundException('Unable to find GamePage.');
        }

        $editForm   = $this->createForm(new GamePageType(), $gamePage);
        $deleteForm = $this->createDeleteForm($id);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The game page was saved!');

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
        /** @var $gamePage \Platformd\SpoutletBundle\Entity\GamePage */
        $gamePage = $form->getData();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->get('platformd.model.game_page_manager')->saveGamePage($gamePage);

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
        $this->getBreadcrumbs()->addChild('Game Pages', array(
            'route' => 'admin_game_page'
        ));

        return $this->getBreadcrumbs();
    }
}
