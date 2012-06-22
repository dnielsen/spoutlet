<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Superclass\JoinedLocale;

/**
 * Effectively a many-to-many join table between GamePage and locale (which is not a real table)
 *
 * @ORM\Table(
 *      name="pd_game_page_locale",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="game_page_locale",
 *              columns={"game_page_id", "locale"}
 *          )
 *      }
 * )
 * @ORM\Entity()
 */
class GamePageLocale extends JoinedLocale
{
    /**
     * @var GamePage
     *
     * @ORM\ManyToOne(targetEntity="GamePage", inversedBy="gamePageLocales")
     * @ORM\JoinColumn(onDelete="CASCADE", name="game_page_id")
     */
    private $gamePage;

    /**
     * @return \Platformd\SpoutletBundle\Entity\GamePage
     */
    public function getGamePage()
    {
        return $this->gamePage;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\GamePage $gamePage
     */
    public function setGamePage($gamePage)
    {
        $this->gamePage = $gamePage;
    }
}