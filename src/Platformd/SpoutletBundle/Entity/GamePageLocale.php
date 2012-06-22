<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Locale\JoinedLocaleInterface;

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
class GamePageLocale implements JoinedLocaleInterface
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length=255)
     */
    private $locale;

    /**
     * @var GamePage
     *
     * @ORM\ManyToOne(targetEntity="GamePage", inversedBy="gamePageLocales")
     * @ORM\JoinColumn(onDelete="CASCADE", name="game_page_id")
     */
    private $gamePage;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

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