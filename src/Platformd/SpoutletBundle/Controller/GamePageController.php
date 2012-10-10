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

        $firstGame = $this->getGamePageManager()->findMostRecentGamePageForAge($this->getAgeManager()->getUsersAge());

        // todo - make this the real query, this is easy while stubbing out the look of the page
        $archives = $this->getGamePageManager()->findArchives();

        $actionPages    = $this->getGamePageManager()->findAllByGamePagesByCategory('action');
        $rpgPages       = $this->getGamePageManager()->findAllByGamePagesByCategory('rpg');
        $strategyPages  = $this->getGamePageManager()->findAllByGamePagesByCategory('strategy');
        $otherPages     = $this->getGamePageManager()->findAllByGamePagesByCategory('other');

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
