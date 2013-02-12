<?php

namespace Platformd\SpoutletBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Platformd\GameBundle\Entity\Game;
use Platformd\GameBundle\Entity\GamePage;
use Platformd\SpoutletBundle\Entity\Site;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadGames extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function load($manager)
    {

        $game1 = new Game();
        $game1->setCategory('action');
        $game1->setName('Battlefield3');
        $manager->persist($game1);

        $game2 = new Game();
        $game2->setCategory('rpg');
        $game2->setName('Warcraft3');
        $manager->persist($game2);

        $game3 = new Game();
        $game3->setCategory('other');
        $game3->setName('Brawl Busters');
        $manager->persist($game3);

        $game4 = new Game();
        $game4->setCategory('rpg');
        $game4->setName('Skyrim');
        $manager->persist($game4);

        $game5 = new Game();
        $game5->setCategory('rpg');
        $game5->setName('Diablo III');
        $manager->persist($game5);

        $manager->flush();

        $gpManager = $this->getGamePageManager();
        $siteRepo  = $manager->getRepository('SpoutletBundle:Site');

        $gamePage1 = new GamePage();
        $gamePage1->setGame($game1);
        $gamePage1->setAboutGame('Lorem ipsum');
        $gamePage1->setYoutubeIdTrailer1('SaiIbN-TqJA');
        $gamePage1->setYoutubeIdTrailer2('NDDfPxF3EFE');
        $gamePage1->setYoutubeIdTrailer3('WD8HF-AL2yY');
        $gamePage1->setYoutubeIdTrailer4('Qkp2SAPLuDk');
        $gamePage1->setStatus(GamePage::STATUS_PUBLISHED);
        $gamePage1->setSites(array($siteRepo->find(1), $siteRepo->find(2)));

        $gpManager->saveGamePage($gamePage1);

        $gamePage2 = new GamePage();
        $gamePage2->setGame($game2);
        $gamePage2->setAboutGame('Lorem ipsum');
        $gamePage2->setYoutubeIdTrailer1('vBODH8ElBak');
        $gamePage2->setYoutubeIdTrailer2('Ggxgp99yvi0');
        $gamePage2->setYoutubeIdTrailer3('y_CIzFVRvLU');
        $gamePage2->setYoutubeIdTrailer4('2f96hKqkY_Y');
        $gamePage2->setStatus(GamePage::STATUS_PUBLISHED);
        $gamePage2->setSites(array($siteRepo->find(1), $siteRepo->find(3)));

        $gpManager->saveGamePage($gamePage2);

        $gamePage3 = new GamePage();
        $gamePage3->setGame($game3);
        $gamePage3->setAboutGame('Lorem ipsum archived');
        $gamePage3->setYoutubeIdTrailer1('vBODH8ElBak');
        $gamePage3->setYoutubeIdTrailer2('Ggxgp99yvi0');
        $gamePage3->setYoutubeIdTrailer3('y_CIzFVRvLU');
        $gamePage3->setYoutubeIdTrailer4('2f96hKqkY_Y');
        $gamePage3->setStatus(GamePage::STATUS_ARCHIVED);
        $gamePage3->setSites(array($siteRepo->find(1), $siteRepo->find(2)));

        $gpManager->saveGamePage($gamePage3);

        $gamePage4 = new GamePage();
        $gamePage4->setGame($game4);
        $gamePage4->setExternalUrl("https://www.google.com/search?btnG=1&pws=0&q=skyrim");
        $gamePage4->setStatus(GamePage::STATUS_ARCHIVED);
        $gamePage4->setSites(array($siteRepo->find(1), $siteRepo->find(2)));

        $gpManager->saveGamePage($gamePage4);
    }

    /**
     * @return \Platformd\GameBundle\Model\GamePageManager
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

?>
