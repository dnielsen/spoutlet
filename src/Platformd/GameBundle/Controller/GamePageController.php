<?php

namespace Platformd\GameBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
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

        $site           = $this->getCurrentSite();

        $firstGame      = $mgr->findMostRecentGamePageForAge($this->getAgeManager()->getUsersAge(), $site);
        $archives       = $mgr->findArchives($site);

        $actionPages    = $mgr->findAllByGamePagesByCategory('action', $site, 9);
        $rpgPages       = $mgr->findAllByGamePagesByCategory('rpg', $site, 9);
        $strategyPages  = $mgr->findAllByGamePagesByCategory('strategy', $site, 9);
        $otherPages     = $mgr->findAllByGamePagesByCategory('other', $site, 9);


        $displayedGamePages     = array($actionPages, $rpgPages, $strategyPages, $otherPages);
        $displayedGamePageIds   = array();

        foreach ($displayedGamePages as $gamePageGroup) {
            foreach ($gamePageGroup as $gamePage) {
                $displayedGamePageIds[] = $gamePage->getId();
            }
        }

        $publishedArchives = $mgr->findAllGamePagesWhereIdNotIn($displayedGamePageIds, $site);

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

        $site = $this->getCurrentSite();

        $gamePage = $this->getGamePageManager()->findOneBySlug($slug, $site);
        if (!$gamePage) {
            throw $this->createNotFoundException('No game page found in this site for slug '.$slug);
        }

        // giveaways, sweepstakes related to this game and active

        $feedGiveaways = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway')
            ->findActivesForGame($gamePage->getGame(), $site)
        ;

        $dealRepo = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Deal');

        $deals          = $dealRepo->findAllPublishedForSiteNewestFirstForGame($site, $gamePage->getGame());
        $feedNewsItems  = $this->getNewsRepo()->findActivesForGame($gamePage->getGame(), $site);

        $hasVideos = $gamePage->getyoutubeIdTrailer1() != ''
            || $gamePage->getyoutubeIdTrailer1() != ''
            || $gamePage->getyoutubeIdTrailer1() != ''
            || $gamePage->getyoutubeIdTrailer1() != '';

        $events = $this->getGlobalEventService()->findEventsForGamePage($site, $gamePage->getGame());

        $hasFeedItems = count($deals) > 0 || count($feedNewsItems) > 0 || $hasVideos || count($events) > 0;

        $hasFeatures = $gamePage->getKeyFeature1() != ''
            || $gamePage->getKeyFeature2() != ''
            || $gamePage->getKeyFeature3() != '';



        return array(
            'gamePage'      => $gamePage,
            'feedNewsItems' => $feedNewsItems,
            'feedDeals'     => $deals,
            'hasVideos'     => $hasVideos,
            'hasFeedItems'  => $hasFeedItems,
            'hasFeatures'   => $hasFeatures,
            'feedGiveaways' => $feedGiveaways,
            'events'        => $events,
        );
    }

    /**
     * @return \Platformd\GameBundle\Model\GamePageManager
     */
    private function getGamePageManager()
    {
        return $this->get('platformd.model.game_page_manager');
    }
}
