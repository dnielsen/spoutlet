<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\GamePage;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\GamePageLocale;

/**
 * Manager for GamePage.
 *
 * In particular, this transforms a "locales" array property to a proper "hasMany" relationship
 *
 * This helps us overcome the fact that "locales" aren't a database object,
 * so creating a classic ManyToMany to it actually involves creating a OneToMany
 * to a new entity. Getting that in a form is hard, unless we can just set
 * a locales array and have a listener handle the relationship.
 */
class GamePageManager
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Call this to save a GamePage
     *
     * @param \Platformd\SpoutletBundle\Entity\GamePage $gamePage
     */
    public function saveGamePage(GamePage $gamePage)
    {
        $this->em->persist($gamePage);

        $this->handleLocales($gamePage);
        $this->handleMediaFields($gamePage);
    }

    /**
     * Handles the complex locales setup
     *
     * @param \Platformd\SpoutletBundle\Entity\GamePage $gamePage
     */
    private function handleLocales(GamePage $gamePage)
    {
        $remainingNewLocales = $gamePage->getLocales();

        /** @var $gamePageLocale \Platformd\SpoutletBundle\Entity\GamePageLocale */
        foreach ($gamePage->getGamePageLocales() as $gamePageLocale) {
            if (in_array($gamePageLocale->getLocale(), $remainingNewLocales)) {
                // no need to remove this GamePageLocale
                // but let's make sure it doesn't get added new
                $key = array_search($gamePageLocale->getLocale(), $remainingNewLocales);
                unset($remainingNewLocales[$key]);
            } else {
                // this GamePageLocale is no longer being used... murder it!
                $this->em->remove($gamePageLocale);
                // update the inverse side for good measure
                $gamePage->getGamePageLocales()->removeElement($gamePageLocale);
            }
        }

        // now add the new guys
        foreach ($remainingNewLocales as $newLocale) {
            $newGamePageLocale = new GamePageLocale();
            $newGamePageLocale->setGamePage($gamePage);
            $newGamePageLocale->setLocale($newLocale);
            $this->em->persist($newGamePageLocale);

            // set the inverse side
            $gamePage->getGamePageLocales()->add($newGamePageLocale);
        }

        $this->em->flush();
    }

    /**
     * Properly persists or unsets the media fields
     *
     * @param \Platformd\SpoutletBundle\Entity\GamePage $gamePage
     */
    private function handleMediaFields(GamePage $gamePage)
    {
        // todo
    }
}