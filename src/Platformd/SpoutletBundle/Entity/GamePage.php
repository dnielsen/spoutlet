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
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\Validator\ExecutionContext;

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
 * @Assert\Callback(methods={"validateExternalArchivedGamePage", "validatePossibilityOfSlug"})
 */
class GamePage implements LinkableInterface
{
    const STATUS_PUBLISHED      = 'published';
    const STATUS_UNPUBLISHED    = 'unpublished';
    const STATUS_ARCHIVED       = 'archived';

    private static $validStatues = array(
        self::STATUS_PUBLISHED,
        self::STATUS_UNPUBLISHED,
        self::STATUS_ARCHIVED,
    );

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
     * This is *almost* required. It's not because archived game pages
     * can be created with an external URL that simply point to the old
     * site. Yea, that's totally a hack, but it's what they want. These
     * MUST be archived or else things will go crazy.
     *
     * @var \Platformd\SpoutletBundle\Entity\Game
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Game", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    private $game;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="external_url", length="255", nullable=true)
     */
    private $externalUrl;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="recommended_desktop_url", length="255", nullable=true)
     */
    private $recommendedDesktopUrl;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="recommended_laptop_url", length="255", nullable=true)
     */
    private $recommendedLaptopUrl;

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
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer1Headline;

    /**
     * @var string $youtubeIdTrailer2
     *
     * @ORM\Column(name="youtubeIdTrailer2", type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer2;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer2Headline;

    /**
     * @var string $youtubeIdTrailer3
     *
     * @ORM\Column(name="youtubeIdTrailer3", type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer3;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer3Headline;

    /**
     * @var string $youtubeIdTrailer4
     *
     * @ORM\Column(name="youtubeIdTrailer4", type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer4;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $youtubeIdTrailer4Headline;

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

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_game_page_site")
     */
     private $sites;

    /**
     * The published/unpublished/archived field
     *
     * @var string
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     * @Assert\NotBlank(message="error.select_status")
     */
    private $status;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\MediaBundle\Entity\Media")
     * @ORM\JoinTable(
     *   name="pd_game_page_gallery_media",
     *   joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *   inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     */
    protected $mediaGalleryMedias;

    /**
     *
     * @var OpenGraphOverride
     * @ORM\OneToOne(targetEntity="OpenGraphOverride", cascade={"persist"})
     */
    private $openGraphOverride;

    /**
     * @ORM\Column(name="sitified_at", type="datetime", nullable="true")
     */
    protected $sitifiedAt;

    public function __construct()
    {
        $this->gamePageLocales = new ArrayCollection();
        $this->mediaGalleryMedias = new ArrayCollection();
        $this->sites = new ArrayCollection();
    }

    public function setSitifiedAt($sitifiedAt)
    {
        $this->sitifiedAt = $sitifiedAt;
    }

    public function getSitifiedAt()
    {
        return $this->sitifiedAt;
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
     * @param string $externalUrl
     */
    public function setExternalUrl($externalUrl) {
        $this->externalUrl = $externalUrl;
    }

    /**
     * @return string
     */
    public function getExternalUrl() {
        return $this->externalUrl;
    }

    /**
     * @param string $recommendedDesktopUrl
     */
    public function setRecommendedDesktopUrl($recommendedDesktopUrl) {
        $this->recommendedDesktopUrl = $recommendedDesktopUrl;
    }

    /**
     * @return string
     */
    public function getRecommendedDesktopUrl() {
        return $this->recommendedDesktopUrl;
    }

    /**
     * @param string $recommendedLaptopUrl
     */
    public function setRecommendedLaptopUrl($recommendedLaptopUrl) {
        $this->recommendedLaptopUrl = $recommendedLaptopUrl;
    }

    /**
     * @return string
     */
    public function getRecommendedLaptopUrl() {
        return $this->recommendedLaptopUrl;
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
     * @return bool
     */
    public function hasButtons()
    {
        return (bool) $this->buttonImage1 || $this->buttonImage2;
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
        $this->locales;
    }

        public function setLocales(array $locales)
    {
        $this->locales = $locales;

        // force Doctrine to see this as dirty
        $this->updatedAt = new \DateTime();
    }

    public function getGamePageLocales()
    {
        return $this->gamePageLocales;
    }

    public function setGamePageLocales($gamePageLocales)
    {
        $this->gamePageLocales = $gamePageLocales;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $sites
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }


    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function isPublished()
    {
        return $this->status == self::STATUS_PUBLISHED;
    }

    public function isArchived()
    {
        return $this->status == self::STATUS_ARCHIVED;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        if ($status && !in_array($status, self::$validStatues)) {
            throw new \InvalidArgumentException(sprintf('Invalid status passed: "%s"', $status));
        }

        $this->status = $status;
    }

    /**
     * @static
     * @return array
     */
    static public function getValidStatues()
    {
        return self::$validStatues;
    }

    /**
     * If there is a set URL that should be used without doing anything else, return it here
     *
     * @return string
     */
    public function getLinkableOverrideUrl()
    {
        return $this->getExternalUrl();
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    public function  getLinkableRouteName()
    {
        // todo - fill this in when we have a show page
        return 'game_page_show';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function  getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug(),
            'category' => $this->getGame()->getCategory(),
        );
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMediaGalleryMedias()
    {
        return $this->mediaGalleryMedias;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getLogo()
    {
        return $this->getGame()->getLogo();
    }

    /**
     * @return string
     */
    public function getYoutubeIdTrailer1Headline()
    {
        return $this->youtubeIdTrailer1Headline;
    }

    /**
     * @param string $youtubeIdTrailer1Headline
     */
    public function setYoutubeIdTrailer1Headline($youtubeIdTrailer1Headline)
    {
        $this->youtubeIdTrailer1Headline = $youtubeIdTrailer1Headline;
    }

    /**
     * @return string
     */
    public function getYoutubeIdTrailer2Headline()
    {
        return $this->youtubeIdTrailer2Headline;
    }

    /**
     * @param string $youtubeIdTrailer2Headline
     */
    public function setYoutubeIdTrailer2Headline($youtubeIdTrailer2Headline)
    {
        $this->youtubeIdTrailer2Headline = $youtubeIdTrailer2Headline;
    }

    /**
     * @return string
     */
    public function getYoutubeIdTrailer3Headline()
    {
        return $this->youtubeIdTrailer3Headline;
    }

    /**
     * @param string $youtubeIdTrailer3Headline
     */
    public function setYoutubeIdTrailer3Headline($youtubeIdTrailer3Headline)
    {
        $this->youtubeIdTrailer3Headline = $youtubeIdTrailer3Headline;
    }

    /**
     * @return string
     */
    public function getYoutubeIdTrailer4Headline()
    {
        return $this->youtubeIdTrailer4Headline;
    }

    /**
     * @param string $youtubeIdTrailer4Headline
     */
    public function setYoutubeIdTrailer4Headline($youtubeIdTrailer4Headline)
    {
        $this->youtubeIdTrailer4Headline = $youtubeIdTrailer4Headline;
    }

    /**
     * It's ok to not choose a game, but ONLY if there is an external URL
     * and this game is archived.
     *
     * @param \Symfony\Component\Validator\ExecutionContext $executionContext
     */
    public function validateExternalArchivedGamePage(ExecutionContext $executionContext)
    {
        // we have a game... cool
        if ($this->getGame()) {
            return;
        }

        if (!$this->getExternalUrl() || !$this->getStatus() == self::STATUS_ARCHIVED) {
            $propertyPath = $executionContext->getPropertyPath() . '.game';
            $executionContext->setPropertyPath($propertyPath);

            $executionContext->addViolation(
                'You must either choose a game or give this game page an External URL and mark it as archived so that it does not have a real page on the site',
                array(),
                null
            );
        }
    }

    public function getBestNameForGame()
    {
        if ($this->getGame()) {
            $gameName = $this->getGame()->getName();

            if (strlen($gameName) > 0) {
                return $gameName;
            }
        }

        $slug = $this->getSlug();

        if (strlen($slug) > 0) {
            return $slug;
        }

        $externalUrl = $this->getExternalUrl();

        if (strlen($externalUrl) > 0) {
            return $externalUrl;
        }

        return "Unknown Game Name";
    }

    /**
     * Validates that if there is no slug and not game is set, we need to
     * tell the user to manually set the slug.
     *
     * @param \Symfony\Component\Validator\ExecutionContext $executionContext
     */
    public function validatePossibilityOfSlug(ExecutionContext $executionContext)
    {
        if ($this->getSlug()) {
            return;
        }

        if ($this->getGame()) {
            return;
        }

        $propertyPath = $executionContext->getPropertyPath() . '.slug';
        $executionContext->setPropertyPath($propertyPath);

        $executionContext->addViolation(
            'If this game page has no related game, you must manually set the URL string. This string is not used, but it must be set to something unique.',
            array(),
            null
        );
    }

    /**
     * @return OpenGraphOverride
     */
    public function getOpenGraphOverride()
    {
        return $this->openGraphOverride;
    }

    /**
     * @param OpenGraphOverride $openGraphOverride
     */
    public function setOpenGraphOverride(OpenGraphOverride $openGraphOverride = null)
    {
        $this->openGraphOverride = $openGraphOverride;
    }
}
