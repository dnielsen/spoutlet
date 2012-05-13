<?php

namespace Platformd\SpoutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\SpoutletBundle\Entity\Game;
use Platformd\SpoutletBundle\Entity\GamePage;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadGames extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function load($manager)
    {
        $game1 = new Game();
        $game1->setCategory('rpg');
        $game1->setName('Battlefield3');
        $manager->persist($game1);

        $game2 = new Game();
        $game2->setCategory('mmo');
        $game2->setName('Warcraft3');
        $manager->persist($game2);

        $manager->flush();

        $gamePage1 = new GamePage();
        $gamePage1->setGame($game1);
        $gamePage1->setAboutGame('Lorem ipsum');
        $gamePage1->setYoutubeIdTrailer1('SaiIbN-TqJA');
        $gamePage1->setYoutubeIdTrailer2('NDDfPxF3EFE');
        $gamePage1->setYoutubeIdTrailer3('WD8HF-AL2yY');
        $gamePage1->setYoutubeIdTrailer4('Qkp2SAPLuDk');
        $gamePage1->setStatus(GamePage::STATUS_PUBLISHED);
        $gamePage1->setLocales(array('en', 'ja'));
        $manager->persist($gamePage1);
        $this->getGamePageManager()->saveGamePage($gamePage1);

        $gamePage2 = new GamePage();
        $gamePage2->setGame($game2);
        $gamePage2->setAboutGame('Lorem ipsum');
        $gamePage2->setYoutubeIdTrailer1('vBODH8ElBak');
        $gamePage2->setYoutubeIdTrailer2('Ggxgp99yvi0');
        $gamePage2->setYoutubeIdTrailer3('y_CIzFVRvLU');
        $gamePage2->setYoutubeIdTrailer4('2f96hKqkY_Y');
        $gamePage2->setStatus(GamePage::STATUS_PUBLISHED);
        $gamePage2->setLocales(array('en', 'zh'));
        $this->getGamePageManager()->saveGamePage($gamePage2);
    }

    /**
     * @return \Platformd\SpoutletBundle\Model\GamePageManager
     */
    private function getGamePageManager()
    {
        return $this->container->get('platformd.model.game_page_manager');
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }


    public function getOrder()
    {
        return 2;
    }
}