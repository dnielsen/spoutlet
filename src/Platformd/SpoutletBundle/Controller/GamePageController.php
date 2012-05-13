<?php

namespace Platformd\SpoutletBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class GamePageController extends Controller
{
    const AGE_LIMIT = 13;

    /**
     * The main games "list" page
     * @Template
     */
    public function indexAction()
    {
        $this->enforceAgeProtection(self::AGE_LIMIT);
        // todo - add the age check

        $categorizedGames = $this->getGamePageManager()
            ->findActiveGamesInCategoriesForAge($this->getAgeManager()->getUsersAge())
        ;

        $firstGame = (count($categorizedGames)) > 0 ? $categorizedGames : null;

        // todo - make this the real query, this is easy while stubbing out the look of the page
        $archives = $this->getGamePageManager()->findArchives();

        return array(
            'categorizedGames'  => $categorizedGames,
            'firstGame'         => $firstGame,
            'archives'          => $archives,
        );
    }

    /**
     * @param string $slug
     * @Template()
     */
    public function showAction($slug)
    {
        $gamePage = $this->getGamePageManager()->findOneBySlug($slug);
        if (!$gamePage) {
            throw $this->createNotFoundException('No game page found in this site for slug '.$slug);
        }

        return array(
            'gamePage' => $gamePage,
        );
    }

    /**
     * @return \Platformd\SpoutletBundle\Model\GamePageManager
     */
    private function getGamePageManager()
    {
        return $this->get('platformd.model.game_page_manager');
    }
}