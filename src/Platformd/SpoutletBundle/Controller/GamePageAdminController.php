<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\GamePage;
use Platformd\SpoutletBundle\Form\Type\GamePageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

/**
 * GamePage admin controller.
 *
 */
class GamePageAdminController extends Controller
{
    /**
     * Lists all locales - a gateway to the "list" action
     */
    public function indexAction()
    {
        $this->addGamePagesBreadcrumb();

        return $this->render('SpoutletBundle:GamePageAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    /**
     * Lists all GamePage entities for a site
     *
     */
    public function listAction($site)
    {
        $this->addGamePagesBreadcrumb();
        $this->addSiteBreadcrumbs($site);

        $gamePages = $this->getGamePageManager()->findAllForSiteNewestFirst($site);

        return $this->render('SpoutletBundle:GamePageAdmin:list.html.twig', array(
            'entities' => $gamePages,
            'site'     => $site,
        ));
    }

    /**
     * Creates a new GamePage gamePage.
     *
     */
    public function newAction(Request $request, $site = null)
    {
        $this->addGamePagesBreadcrumb();
        $this->addSiteBreadcrumbs($site)->addChild('New Game Page');

        $gamePage  = new GamePage();
        $form    = $this->createForm(new GamePageType(), $gamePage);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The game page was created!');

            return $this->redirect($this->generateUrl('admin_game_page_edit', array(
                'id' => $gamePage->getId(),
                'site' => $site,
            )));
        }

        return $this->render('SpoutletBundle:GamePageAdmin:new.html.twig', array(
            'entity' => $gamePage,
            'form'   => $form->createView(),
            'site'   => $site,
        ));
    }

    /**
     * Edits an existing GamePage gamePage.
     *
     */
    public function editAction($id, Request $request, $site = null)
    {
        $this->addGamePagesBreadcrumb();
        $this->addSiteBreadcrumbs($site)->addChild('Edit Game Page');
        $em = $this->getDoctrine()->getEntityManager();

        $gamePage = $em->getRepository('SpoutletBundle:GamePage')->find($id);

        if (!$gamePage) {
            throw $this->createNotFoundException('Unable to find GamePage.');
        }

        $editForm   = $this->createForm(new GamePageType(), $gamePage);
        $deleteForm = $this->createDeleteForm($id);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The game page was saved!');

            return $this->redirect($this->generateUrl('admin_game_page_edit', array(
                'id' => $id,
                'site' => $site,
            )));
        }

        return $this->render('SpoutletBundle:GamePageAdmin:edit.html.twig', array(
            'gamePage'      => $gamePage,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'site'          => $site,
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
                $this->getGamePageManager()->saveGamePage($gamePage);

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

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild(MultitenancyManager::getSiteName($site), array(
                'route' => 'admin_game_page_site',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }


    /**
     * @return \Platformd\SpoutletBundle\Model\GamePageManager
     */
    private function getGamePageManager()
    {
        return $this->get('platformd.model.game_page_manager');
    }
}
