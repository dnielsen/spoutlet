<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\MediaBundle\Entity\Media;
use Platformd\SpoutletBundle\Entity\Game;
use Gedmo\Sluggable\Util\Urlizer;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Platformd\SpoutletBundle\Entity\GamePage
 *
 * @ORM\Table(
 *      name="pd_game_page",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="slug_unique",
 *              columns={"slug"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GamePageRepository")
 * @UniqueEntity(fields={"slug"}, message="This URL is already used. Or if this is blank, there may already be a game page for this game, and if you intend to make a second page, please enter a unique URL string for it")
 */
class GamePage
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
     * @var string $slug
     *
     * Generated when you set the "Game", or can be set manually
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @Assert\NotBlank
     *
     * @var \Platformd\SpoutletBundle\Entity\Game
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Game", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $game;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $buttonImage1;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="button_image1", length="255", nullable=true)
     */
    private $buttonUrl1;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $buttonImage2;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="button_image2", length="255", nullable=true)
     */
    private $buttonUrl2;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $backgroundImage;

    /**
     * @var text $aboutGame
     *
     * @ORM\Column(name="aboutGame", type="text", nullable=true)
     */
    private $aboutGame;

    /**
     * @var text $keyFeature1
     *
     * @ORM\Column(name="keyFeature1", type="text", nullable=true)
     */
    private $keyFeature1;

    /**
     * @var text $keyFeature2
     *
     * @ORM\Column(name="keyFeature2", type="text", nullable=true)
     */
    private $keyFeature2;

    /**
     * @var text $keyFeature3
     *
     * @ORM\Column(name="keyFeature3", type="text", nullable=true)
     */
    private $keyFeature3;

    /**
     * @var string $youtubeTrailer1
     *
     * @ORM\Column(name="youtubeTrailer1", type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer1;

    /**
     * @var string $youtubeIdTrailer2
     *
     * @ORM\Column(name="youtubeIdTrailer2", type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer2;

    /**
     * @var string $youtubeIdTrailer3
     *
     * @ORM\Column(name="youtubeIdTrailer3", type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer3;

    /**
     * @var string $youtubeIdTrailer4
     *
     * @ORM\Column(name="youtubeIdTrailer4", type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer4;

    /**
     * @var text $legalVerbiage
     *
     * @ORM\Column(name="legalVerbiage", type="text", nullable=true)
     */
    private $legalVerbiage;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * Holds the "many" locales relationship
     *
     * Don't set this directly, instead set "locales" directly, and a listener
     * will take care of properly creating the GamePageLocale relationship
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="GamePageLocale", orphanRemoval=true, mappedBy="gamePage")
     */
    private $gamePageLocales;

    private $locales;

    public function __construct()
    {
        $this->gamePageLocales = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        // don't let the slug be blanked out
        // this allows the user to not enter a slug in the form. The slug
        // will be generated from the name, but not overridden by that blank
        // slug value
        if (!$slug) {
            return;
        }

        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set aboutGame
     *
     * @param text $aboutGame
     */
    public function setAboutGame($aboutGame)
    {
        $this->aboutGame = $aboutGame;
    }

    /**
     * Get aboutGame
     *
     * @return text 
     */
    public function getAboutGame()
    {
        return $this->aboutGame;
    }

    /**
     * Set keyFeature1
     *
     * @param text $keyFeature1
     */
    public function setKeyFeature1($keyFeature1)
    {
        $this->keyFeature1 = $keyFeature1;
    }

    /**
     * Get keyFeature1
     *
     * @return text 
     */
    public function getKeyFeature1()
    {
        return $this->keyFeature1;
    }

    /**
     * Set keyFeature2
     *
     * @param text $keyFeature2
     */
    public function setKeyFeature2($keyFeature2)
    {
        $this->keyFeature2 = $keyFeature2;
    }

    /**
     * Get keyFeature2
     *
     * @return text 
     */
    public function getKeyFeature2()
    {
        return $this->keyFeature2;
    }

    /**
     * Set keyFeature3
     *
     * @param text $keyFeature3
     */
    public function setKeyFeature3($keyFeature3)
    {
        $this->keyFeature3 = $keyFeature3;
    }

    /**
     * Get keyFeature3
     *
     * @return text 
     */
    public function getKeyFeature3()
    {
        return $this->keyFeature3;
    }

    /**
     * Set youtubeTrailer1
     *
     * @param string $youtubeTrailer1
     */
    public function setYoutubeIdTrailer1($youtubeTrailer1)
    {
        $this->youtubeIdTrailer1 = $youtubeTrailer1;
    }

    /**
     * Get youtubeTrailer1
     *
     * @return string 
     */
    public function getYoutubeIdTrailer1()
    {
        return $this->youtubeIdTrailer1;
    }

    /**
     * Set youtubeIdTrailer2
     *
     * @param string $youtubeIdTrailer2
     */
    public function setYoutubeIdTrailer2($youtubeIdTrailer2)
    {
        $this->youtubeIdTrailer2 = $youtubeIdTrailer2;
    }

    /**
     * Get youtubeIdTrailer2
     *
     * @return string 
     */
    public function getYoutubeIdTrailer2()
    {
        return $this->youtubeIdTrailer2;
    }

    /**
     * Set youtubeIdTrailer3
     *
     * @param string $youtubeIdTrailer3
     */
    public function setYoutubeIdTrailer3($youtubeIdTrailer3)
    {
        $this->youtubeIdTrailer3 = $youtubeIdTrailer3;
    }

    /**
     * Get youtubeIdTrailer3
     *
     * @return string 
     */
    public function getYoutubeIdTrailer3()
    {
        return $this->youtubeIdTrailer3;
    }

    /**
     * Set youtubeIdTrailer4
     *
     * @param string $youtubeIdTrailer4
     */
    public function setYoutubeIdTrailer4($youtubeIdTrailer4)
    {
        $this->youtubeIdTrailer4 = $youtubeIdTrailer4;
    }

    /**
     * Get youtubeIdTrailer4
     *
     * @return string 
     */
    public function getYoutubeIdTrailer4()
    {
        return $this->youtubeIdTrailer4;
    }

    /**
     * Set legalVerbiage
     *
     * @param text $legalVerbiage
     */
    public function setLegalVerbiage($legalVerbiage)
    {
        $this->legalVerbiage = $legalVerbiage;
    }

    /**
     * Get legalVerbiage
     *
     * @return text 
     */
    public function getLegalVerbiage()
    {
        return $this->legalVerbiage;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getButtonImage1()
    {
        return $this->buttonImage1;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $buttonImage1
     */
    public function setButtonImage1(Media $buttonImage1 = null)
    {
        $this->buttonImage1 = $buttonImage1;
    }

    /**
     * @return string
     */
    public function getButtonUrl1()
    {
        return $this->buttonUrl1;
    }

    /**
     * @param string $buttonUrl1
     */
    public function setButtonUrl1($buttonUrl1)
    {
        $this->buttonUrl1 = $buttonUrl1;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getButtonImage2()
    {
        return $this->buttonImage2;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $buttonImage2
     */
    public function setButtonImage2(Media $buttonImage2 = null)
    {
        $this->buttonImage2 = $buttonImage2;
    }

    /**
     * @return string
     */
    public function getButtonUrl2()
    {
        return $this->buttonUrl2;
    }

    /**
     * @param string $buttonUrl2
     */
    public function setButtonUrl2($buttonUrl2)
    {
        $this->buttonUrl2 = $buttonUrl2;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $backgroundImage
     */
    public function setBackgroundImage(Media $backgroundImage = null)
    {
        $this->backgroundImage = $backgroundImage;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Game $game
     */
    public function setGame(Game $game)
    {
        // sets the, but only if it's blank
        // this is not meant to be smart enough to guarantee correct uniqueness
        // that will happen with validation
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($game->getName());

            $this->setSlug($slug);
        }

        $this->game = $game;
    }

    public function getLocales()
    {
        return is_array($this->locales) ? $this->locales : array();
    }

    /**
     * The locales are null until someone actually sets them
     *
     * This allows us to set them on load of the entity based on the relationship,
     * but by checking this, we can be careful not to run over real values
     *
     * @return bool
     */
    public function areLocalesInitialized()
    {
        return is_array($this->locales);
    }

    public function setLocales($locales)
    {
        $this->locales = $locales;

        // force Doctrine to see this as dirty
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGamePageLocales()
    {
        return $this->gamePageLocales;
    }
}