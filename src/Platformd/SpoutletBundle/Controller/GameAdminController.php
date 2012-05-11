<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Game;
use Platformd\SpoutletBundle\Form\GameType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Game admin controller.
 *
 */
class GameAdminController extends Controller
{
    /**
     * Lists all Game entities.
     *
     */
    public function indexAction()
    {
        $this->addGamesBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('SpoutletBundle:Game')->findAll();

        return $this->render('SpoutletBundle:GameAdmin:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Creates a new Game game.
     *
     */
    public function newAction(Request $request)
    {
        $this->addGamesBreadcrumb()->addChild('New Game');

        $game  = new Game();
        $form    = $this->createForm(new GameType(), $game);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($game);
                $em->flush();

                return $this->redirect($this->generateUrl('admin_game_edit', array('id' => $game->getId())));

            }
        }

        return $this->render('SpoutletBundle:GameAdmin:new.html.twig', array(
            'entity' => $game,
            'form'   => $form->createView()
        ));
    }

    /**
     * Edits an existing Game game.
     *
     */
    public function editAction($id, Request $request)
    {
        $this->addGamesBreadcrumb()->addChild('Edit Game');
        $em = $this->getDoctrine()->getEntityManager();

        $game = $em->getRepository('SpoutletBundle:Game')->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Unable to find Game game.');
        }

        $editForm   = $this->createForm(new GameType(), $game);
        $deleteForm = $this->createDeleteForm($id);

        if ($request->getMethod() == 'POST') {
            $editForm->bindRequest($request);

            if ($editForm->isValid()) {
                $em->persist($game);
                $em->flush();

                return $this->redirect($this->generateUrl('admin_game_edit', array('id' => $id)));
            }
        }

        return $this->render('SpoutletBundle:GameAdmin:edit.html.twig', array(
            'game'      => $game,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Game game.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $game = $em->getRepository('SpoutletBundle:Game')->find($id);

            if (!$game) {
                throw $this->createNotFoundException('Unable to find Game game.');
            }

            $em->remove($game);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_game'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addGamesBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Games', array(
            'route' => 'admin_game'
        ));

        return $this->getBreadcrumbs();
    }
}
