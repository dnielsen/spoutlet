<?php

namespace Platformd\SpoutletBundle\Test\Doctrine;

use Platformd\SpoutletBundle\Test\WebTestCase;
use Platformd\SpoutletBundle\Entity\Game;
use Platformd\SpoutletBundle\Entity\GamePage;

/**
 * Test for ManyLocalesListener
 */
class ManyLocalesListenerTest extends WebTestCase
{
    /**
     * Integration test for setting and removing locales
     */
    public function testIntegration()
    {
        self::createClient();

        $this->emptyModel('SpoutletBundle:Game');
        $this->emptyModel('SpoutletBundle:GamePage');

        $em = $this->getEntityManager();

        $game = new Game();
        $game->setName('Foo');
        $game->setCategory('rpg');
        $em->persist($game);

        $gamePage = new GamePage();
        $gamePage->setGame($game);
        $em->persist($gamePage);

        // flush our starting data
        $em->flush();

        // 1) add a locale
        $gamePage->setLocales(array('en'));
        $em->flush();
        $this->verifyLocales($gamePage, array('en'));

        // 2) add another locale
        $gamePage->setLocales(array('en', 'fr'));
        $em->flush();
        $this->verifyLocales($gamePage, array('en', 'fr'));

        // 3) remove a locale
        $gamePage->setLocales(array('fr'));
        $em->flush();
        $this->verifyLocales($gamePage, array('fr'));
    }

    private function verifyLocales(GamePage $gamePage, array $locales)
    {
        $gamePageLocales = $this->getEntityManager()
            ->getRepository('SpoutletBundle:GamePageLocale')
            ->createQueryBuilder('gpl')
            ->andWhere('gpl.gamePage = :gamePage')
            ->setParameter('gamePage', $gamePage)
            ->getQuery()
            ->execute()
        ;

        $actualLocales = array();
        /** @var $gamePageLocale \Platformd\SpoutletBundle\Entity\GamePageLocale */
        foreach ($gamePageLocales as $gamePageLocale) {
            $actualLocales[] = $gamePageLocale->getLocale();
        }

        $this->assertEquals($locales, $gamePageLocales);
    }

}