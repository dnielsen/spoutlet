<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Game;
use Platformd\SpoutletBundle\Form\GameType;

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
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('SpoutletBundle:Game')->findAll();

        return $this->render('SpoutletBundle:GameAdmin:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Finds and displays a Game game.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $game = $em->getRepository('SpoutletBundle:Game')->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Unable to find Game game.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('SpoutletBundle:GameAdmin:show.html.twig', array(
            'game'      => $game,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Game game.
     *
     */
    public function newAction()
    {
        $game = new Game();
        $form   = $this->createForm(new GameType(), $game);

        return $this->render('SpoutletBundle:GameAdmin:new.html.twig', array(
            'game' => $game,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Game game.
     *
     */
    public function createAction()
    {
        $game  = new Game();
        $request = $this->getRequest();
        $form    = $this->createForm(new GameType(), $game);
        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($game);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_game_edit', array('id' => $game->getId())));
            
        }

        return $this->render('SpoutletBundle:GameAdmin:new.html.twig', array(
            'entity' => $game,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Game game.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $game = $em->getRepository('SpoutletBundle:Game')->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Unable to find Game game.');
        }

        $editForm = $this->createForm(new GameType(), $game);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('SpoutletBundle:GameAdmin:edit.html.twig', array(
            'game'      => $game,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Game game.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $game = $em->getRepository('SpoutletBundle:Game')->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Unable to find Game game.');
        }

        $editForm   = $this->createForm(new GameType(), $game);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bindRequest($request);

        if ($editForm->isValid()) {
            $em->persist($game);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_game_edit', array('id' => $id)));
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
}
