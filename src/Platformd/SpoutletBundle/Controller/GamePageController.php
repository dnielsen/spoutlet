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

        return array(
            'categorizedGames'  => $categorizedGames,
            'firstGame'         => $firstGame,
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