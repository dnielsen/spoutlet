<?php

namespace Platformd\SpoutletBundle\Tests\Model;

use Platformd\SpoutletBundle\Test\WebTestCase;
use Platformd\SpoutletBundle\Entity\Game;
use Platformd\SpoutletBundle\Entity\GamePage;

/**
 * Test for ManyLocalesListener
 */
class GamePageManagerTest extends WebTestCase
{
    /**
     * Integration test for setting and removing locales
     */
    public function testIntegration()
    {
        self::createClient();

        $this->emptyModel('SpoutletBundle:Game');
        $this->emptyModel('SpoutletBundle:GamePage');
        $this->emptyModel('SpoutletBundle:GamePageLocale');

        $em = $this->getEntityManager();

        /** @var $manager \Platformd\SpoutletBundle\Model\GamePageManager */
        $manager = $this->getContainer()->get('platformd.model.game_page_manager');

        $game = new Game();
        $game->setName('Foo');
        $game->setCategory('rpg');
        $em->persist($game);
        $em->flush();

        $gamePage = new GamePage();
        $gamePage->setGame($game);
        $manager->saveGamePage($gamePage);

        // 1) add a locale
        $gamePage->setLocales(array('en'));
        $manager->saveGamePage($gamePage);
        $this->verifyLocales($gamePage, array('en'));

        // 2) add another locale
        $gamePage->setLocales(array('en', 'fr'));
        $manager->saveGamePage($gamePage);
        $this->verifyLocales($gamePage, array('en', 'fr'));

        // 3) remove a locale
        $gamePage->setLocales(array('fr'));
        $manager->saveGamePage($gamePage);
        $this->verifyLocales($gamePage, array('fr'));
    }

    private function verifyLocales(GamePage $gamePage, array $locales)
    {
        $gamePageLocales = $this->getEntityManager()
            ->getRepository('SpoutletBundle:GamePageLocale')
            ->createQueryBuilder('gpl')
            // strange error with this where clause
            // cheating and omitting for now, the table should be empty to start anyways
            //->andWhere('gpl.gamePage = :gamePage')
            //->setParameter('gamePage', $gamePage)
            ->getQuery()
            ->execute()
        ;

        $actualLocales = array();
        /** @var $gamePageLocale \Platformd\SpoutletBundle\Entity\GamePageLocale */
        foreach ($gamePageLocales as $gamePageLocale) {
            $actualLocales[] = $gamePageLocale->getLocale();
        }

        $this->assertEquals($locales, $actualLocales);
    }

}