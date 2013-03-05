<?php

namespace Platformd\GameBundle\Controller;

use Platformd\GameBundle\Entity\GamePage;
use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\GameBundle\Form\Type\GamePageType;
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

        return $this->render('GameBundle:GamePageAdmin:index.html.twig', array(
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

        $siteName = MultitenancyManager::getSiteName($site);

        $gamePages = $this->getGamePageManager()->findAllForSiteNewestFirst($siteName);

        return $this->render('GameBundle:GamePageAdmin:list.html.twig', array(
            'entities' => $gamePages,
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
        $gamePage->setCreatedAt(new \DateTime());
        $form    = $this->createForm(new GamePageType(), $gamePage);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The game page was created!');

            return $this->redirect($this->generateUrl('admin_game_page_edit', array(
                'id' => $gamePage->getId(),
                'site' => $site,
            )));
        }

        return $this->render('GameBundle:GamePageAdmin:new.html.twig', array(
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

        $gamePage = $em->getRepository('GameBundle:GamePage')->find($id);

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

        return $this->render('GameBundle:GamePageAdmin:edit.html.twig', array(
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
        /** @var $gamePage \Platformd\GameBundle\Entity\GamePage */
        $gamePage = $form->getData();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                /* Use the youtube title if the video id is valid, if not it will return a blank string */
                if(!$gamePage->getYoutubeIdTrailer1Headline()) {
                    $gamePage->setYoutubeIdTrailer1Headline($this->getYoutubeTitle($gamePage->getYoutubeIdTrailer1()));
                }

                if(!$gamePage->getYoutubeIdTrailer2Headline()) {
                    $gamePage->setYoutubeIdTrailer2Headline($this->getYoutubeTitle($gamePage->getYoutubeIdTrailer2()));
                }

                if(!$gamePage->getYoutubeIdTrailer3Headline()) {
                    $gamePage->setYoutubeIdTrailer3Headline($this->getYoutubeTitle($gamePage->getYoutubeIdTrailer3()));
                }

                if(!$gamePage->getYoutubeIdTrailer4Headline()) {
                    $gamePage->setYoutubeIdTrailer4Headline($this->getYoutubeTitle($gamePage->getYoutubeIdTrailer4()));
                }

                $this->getGamePageManager()->saveGamePage($gamePage);

                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    private function getYoutubeTitle($videoId) {

        if (!$videoId) {
            return false;
        }

        $url = 'http://gdata.youtube.com/feeds/api/videos/' . $videoId . '?alt=jsonc&v=2';

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $result = json_decode(curl_exec($curl), true);

        if(array_key_exists('error', $result)) {
            return '';
        }

        return $result['data']['title'];
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
     * @return \Platformd\GameBundle\Model\GamePageManager
     */
    private function getGamePageManager()
    {
        return $this->get('platformd.model.game_page_manager');
    }
}
