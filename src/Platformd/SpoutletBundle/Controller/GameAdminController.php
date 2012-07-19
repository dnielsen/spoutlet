<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Game;
use Platformd\SpoutletBundle\Form\Type\GameType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

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

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The game was created!');

            return $this->redirect($this->generateUrl('admin_game_edit', array('id' => $game->getId())));
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

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The game was saved!');

            return $this->redirect($this->generateUrl('admin_game_edit', array('id' => $id)));
        }

        return $this->render('SpoutletBundle:GameAdmin:edit.html.twig', array(
            'game'      => $game,
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
                /** @var $game \Platformd\SpoutletBundle\Entity\Game */
                $game = $form->getData();
                $em->persist($game);

                $mUtil = $this->getMediaUtil();

                // either persist the logo, or unset it
                if (!$mUtil->persistRelatedMedia($game->getLogo())) {
                    $game->setLogo(null);
                }

                // either persist the logo thumbnail, or unset it
                if (!$mUtil->persistRelatedMedia($game->getLogoThumbnail())) {
                    $game->setLogoThumbnail(null);
                }

                // either persist the publisher logos, or unset it
                if (!$mUtil->persistRelatedMedia($game->getPublisherLogos())) {
                    $game->setPublisherLogos(null);
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
    private function addGamesBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Games', array(
            'route' => 'admin_game'
        ));

        return $this->getBreadcrumbs();
    }
}
