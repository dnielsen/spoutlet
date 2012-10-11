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

        $mgr = $this->getGamePageManager();

        $firstGame      = $mgr->findMostRecentGamePageForAge($this->getAgeManager()->getUsersAge());
        $archives       = $mgr->findArchives();

        $actionPages    = $mgr->findAllByGamePagesByCategory('action', 9);
        $rpgPages       = $mgr->findAllByGamePagesByCategory('rpg', 9);
        $strategyPages  = $mgr->findAllByGamePagesByCategory('strategy', 9);
        $otherPages     = $mgr->findAllByGamePagesByCategory('other', 9);

        $displayedGamePages     = array($actionPages, $rpgPages, $strategyPages, $otherPages);
        $displayedGamePageIds   = array();

        foreach ($displayedGamePages as $gamePageGroup) {
            foreach ($gamePageGroup as $gamePage) {
                $displayedGamePageIds[] = $gamePage->getId();
            }
        }

        $publishedArchives = $mgr->findAllGamePagesWhereIdNotIn($displayedGamePageIds);

        $archives = array_merge($publishedArchives, $archives);

        return array(
            'firstGame'         => $firstGame,
            'archives'          => $archives,
            'actionPages'       => $actionPages,
            'rpgPages'          => $rpgPages,
            'strategyPages'     => $strategyPages,
            'otherPages'        => $otherPages
        );
    }

    /**
     * @param string $slug
     * @Template()
     */
    public function showAction($slug)
    {
        $this->enforceAgeProtection(self::AGE_LIMIT);

        $gamePage = $this->getGamePageManager()->findOneBySlug($slug);
        if (!$gamePage) {
            throw $this->createNotFoundException('No game page found in this site for slug '.$slug);
        }

        // events, giveaways, sweepstakes related to this game and active
        $feedEvents = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->findActivesForGame($gamePage->getGame(), $this->getLocale())
        ;

        $dealRepo = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Deal');

        $deals          = $dealRepo->findAllPublishedForSiteNewestFirstForGame($this->getLocale(), $gamePage->getGame());
        $feedNewsItems  = $this->getNewsRepo()->findActivesForGame($gamePage->getGame(), $this->getLocale());

        $hasVideos = $gamePage->getyoutubeIdTrailer1() != ''
            && $gamePage->getyoutubeIdTrailer1() != ''
            && $gamePage->getyoutubeIdTrailer1() != ''
            && $gamePage->getyoutubeIdTrailer1() != '';

        $hasFeedItems = count($deals) > 0 && count($feedNewsItems) > 0 && count($feedEvents) > 0;

        $hasFeatures = $gamePage->getKeyFeature1() != ''
            && $gamePage->getKeyFeature2() != ''
            && $gamePage->getKeyFeature3() != '';

        return array(
            'gamePage' => $gamePage,
            'feedEvents' => $feedEvents,
            'feedNewsItems' => $feedNewsItems,
            'feedDeals' => $deals,
            'hasVideos' => $hasVideos,
            'hasFeedItems' => $hasFeedItems,
            'hasFeatures' => $hasFeatures,
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
