<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection
;
use Platformd\SpoutletBundle\Entity\AbstractEvent,
    Platformd\GiveawayBundle\Entity\GiveawayPool,
    Platformd\MediaBundle\Entity\Media,
    Platformd\SpoutletBundle\Model\CommentableInterface,
    Platformd\UserBundle\Entity\User,
    Platformd\SpoutletBundle\Link\LinkableInterface,
    Platformd\SpoutletBundle\Entity\Site
;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\HttpFoundation\File\File
;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Sluggable\Util\Urlizer;

use DateTime;

/**
 * Platformd\GiveawayBundle\Entity\Giveaway
 * @ORM\Table(
 *      name="pd_giveaway",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="slug_unique",
 *              columns={"slug"}
 *          )
 *      }
 * )
 * @UniqueEntity(fields={"slug"}, message="This URL is already used.  If you have left slug blank, this means that an existing giveaway is already using this giveaway name.")
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\GiveawayRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Giveaway implements LinkableInterface, CommentableInterface
{
    const TYPE_KEY_GIVEAWAY = 'key_giveaway'; // the traditional key giveaway type
    const TYPE_MACHINE_CODE_SUBMIT = 'machine_code_submit'; // the machine-submit giveaway type
    const TYPE_TEXT_PREFIX = 'giveaway.type.';
    const REDEMPTION_LINE_PREFIX = '* ';

    static protected $validStatuses = array(
        // totally disabled
        'disabled' => 'platformd.giveaway.status.disabled',
        // active but with zero keys
        'inactive' => 'platformd.giveaway.status.inactive',
        // totally awesome active
        'active' => 'platformd.giveaway.status.active',
    );

    /**
     * A map of UTC offsets and common timezone names
     *
     * This is because all we have are things like "Tokyo", but we may want
     * to actually say JST
     *
     * @var array
     */
    static private $timzoneCommonNames = array(
        32400 => 'JST',
        28800 => 'CST',
    );

    const PREFIX_PATH_BANNER = 'banner/';
    const PREFIX_PATH_GENERAL = 'general/';
    const PREFIX_PATH_BACKGROUND = 'background/';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string $slug
     *
     * Only partially automatically set, through setName()
     *
     * @ORM\Column(name="slug", type="string", length=255)
     * @Assert\Regex(pattern="/^[A-Za-z0-9\-]+$/", message="This can only contain letters, numbers and dashes (-)")
     *      Only allow numbers, digits and dashes
     * This should not happen, since it should generate based on name
     */
    protected $slug;

    /**
     * @var boolean $ready
     *
     * @deprecated I don't think this field was ever used
     * @ORM\Column(name="ready", type="boolean")
     */
    protected $ready = true;

    /**
     * @var boolean $published
     *
     * @ORM\Column(name="published", type="boolean")
     */
    protected $published = false;

    /**
     * @var text $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length="10", nullable=true)
     */
    protected $locale;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_giveaway_site")
     */
     private $sites;


    /**
     * @ORM\Column(name="bannerImage", type="string", length=255, nullable=true)
     */
    protected $bannerImage;

    /**
     * @Assert\File(
     *   maxSize="6000000",
     *   mimeTypes={"image/png", "image/jpeg", "image/jpg"}
     * )
     */
    protected $bannerImageFile;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @var datetime $starts_at
     *
     * @ORM\Column(name="starts_at", type="datetime", nullable=true)
     */
    protected $starts_at;

    /**
     * @var datetime $ends_at
     *
     * @ORM\Column(name="ends_at", type="datetime", nullable=true)
     */
    protected $ends_at;

    /**
     * @ORM\Column(name="generalImage", type="string", length=255, nullable=true)
     */
    protected $generalImage;

    /**
     * @Assert\File(
     *   maxSize="6000000",
     *   mimeTypes={"image/png", "image/jpeg", "image/jpg"}
     * )
     */
    protected $generalImageFile;

    /**
     * The timezone this event is taking place in
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var string
     */
    protected $timezone = 'UTC';

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GameBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Game
     */
    protected $game;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="external_url", length="255", nullable=true)
     */
    private $externalUrl;

    /**
     *
     * @var boolean $displayTimezone
     * @ORM\Column(name="display_timezone", type="boolean")
     *
     */
    protected $display_timezone = true;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset", cascade={"persist"})
     */
    private $ruleset;

    /**
     * @ORM\Column(name="test_only", type="boolean", nullable=true)
     *
     */
    protected $testOnly = false;

    const COMMENT_PREFIX = 'giveaway-';

    /**
     * One to Many with GiveawayPool
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayPool", mappedBy="giveaway")
     */
    protected $pools;

    /**
     * This is a raw HTML field, but with a special format.
     *
     * Each line will be exploded into an array, and used for numbered
     * instructions on the giveaway.
     *
     * @ORM\Column(type="text")
     *
     * @var string
     */
    protected $redemptionInstructions;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayTranslation", mappedBy="translatable", cascade={"all"})
     */
    protected $translations;

    /**
     * A string enum status
     *
     * @var string
     * @ORM\Column(type="string", length=15)
     */
    protected $status = 'disabled';

    /**
     * @var string
     * @ORM\Column(type="string", length=30)
     */
    protected $giveawayType = self::TYPE_KEY_GIVEAWAY;

    protected $currentLocale;

    protected $defaultLocale = 'en';

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $displayRemainingKeysNumber = true;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\GroupBundle\Entity\Group", cascade={"persist"})
     */
    protected $group = null;

    /**
     * @var boolean $featured
     * @ORM\Column(name="featured", type="boolean")
     */
    protected $featured = false;

    /**
     * @var \DateTime $featuredAt
     *
     * @ORM\Column(name="featured_at", type="datetime", nullable=true)
     */
    protected $featuredAt;

    /**
     * The large thumbnail for the giveaway (138px by 83px)
     *
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    protected $thumbnail;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $backgroundImagePath;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $backgroundLink;

    /**
     * @Assert\File(
     *   maxSize="6000000",
     *   mimeTypes={"image/png", "image/jpeg", "image/jpg", "image/gif"}
     * )
     */
    protected $backgroundImage;

    public function __construct()
    {
        // auto-publish, this uses the "status" field instead
        $this->published    = true;
        $this->sites        = new ArrayCollection();
        $this->pools        = new ArrayCollection();
        $this->translations = new ArrayCollection();
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
     * A total hack - so I can safely check for a url redirect on any abstract event in a template
     *
     * @param bool
     */
    public function getUrlRedirect()
    {
        return false;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        // sets the, but only if it's blank
        // this is not meant to be smart enough to guarantee correct uniqueness
        // that will happen with validation
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($name);

            $this->setSlug($slug);
        }
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
     * Set ready
     *
     * @deprecated I don't think this was ever used
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }

    /**
     * Get ready
     *
     * @deprecated I don't think this was ever used
     * @return boolean
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
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
     * @return \Platformd\SpoutletBundle\Entity\File
     */
    public function getBannerImageFile()
    {
        return $this->bannerImageFile;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\File $bannerImageFile
     */
    public function setBannerImageFile($bannerImageFile)
    {
        $this->bannerImageFile = $bannerImageFile;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated(DateTime $updated)
    {
        $this->updated = $updated;
    }

    /**
     * Set starts_at
     *
     * @param \DateTime $startsAt
     */
    public function setStartsAt(DateTime $startsAt = null)
    {
        $this->starts_at = $startsAt;
    }

    /**
     * Get starts_at
     *
     * @return datetime
     */
    public function getStartsAt()
    {
        return $this->starts_at;
    }

    /**
     * Returns the start datetime converted into the timezone of the user
     *
     * @return \DateTime
     */
    public function getStartsAtInTimezone()
    {
        return $this->convertDatetimeToTimezone($this->getStartsAt());
    }

    /**
     * Returns the end datetime converted into the timezone of the user
     *
     * @return \DateTime
     */
    public function getEndsAtInTimezone()
    {
        return $this->convertDatetimeToTimezone($this->getEndsAt());
    }

    /**
     * Returns an array that can be used in a template and passed to a translation string
     *
     * @return array
     */
    public function getStartsAtInTimezoneTranslationArray()
    {
        return self::convertDateTimeIntoTranslationArray($this->getStartsAtInTimezone());
    }

    /**
     * Returns an array that can be used in a template and passed to a translation string
     *
     * @return array
     */
    public function getEndsAtInTimezoneTranslationArray()
    {
        return self::convertDateTimeIntoTranslationArray($this->getEndsAtInTimezone());
    }

    /**
     * @todo - refactor this somewhere more public
     * @static
     * @param \DateTime $dt
     * @return array
     */
    static public function convertDateTimeIntoTranslationArray(DateTime $dt)
    {
        return array(
            '%year%' => $dt->format('Y'),
            '%month%' => $dt->format('m'),
            '%day%' => $dt->format('d'),
            '%time%' => $dt->format('H:i'),
        );
    }

    /**
     * Set ends_at
     *
     * @param \DateTime $endsAt
     */
    public function setEndsAt(DateTime $endsAt = null)
    {
        $this->ends_at = $endsAt;
    }

    /**
     * Get ends_at
     *
     * @return datetime
     */
    public function getEndsAt()
    {
        return $this->ends_at;
    }

    /**
     * Set published
     *
     * @param boolean $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * Get published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    public function getGeneralImage()
    {
        return $this->generalImage;
    }

    public function setGeneralImage($generalImage)
    {
        $this->generalImage = $generalImage;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function getGeneralImageFile()
    {
        return $this->generalImageFile;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\File\File $generalImageFile
     */
    public function setGeneralImageFile(File $generalImageFile)
    {
        $this->generalImageFile = $generalImageFile;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone ? $this->timezone : 'UTC';
    }

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    private function convertDatetimeToTimezone(DateTime $dt)
    {
        $userTimezone = new DateTimeZone($this->getTimezone());
        $offset = $userTimezone->getOffset($dt);

        $timestamp = $dt->format('U') + $offset;

        return DateTime::createFromFormat('U', $timestamp, $userTimezone);
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame($game)
    {
        $this->game = $game;
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

    public function getRuleset()
    {
        return $this->ruleset;
    }

    public function setRuleset($ruleset)
    {
        $this->ruleset = $ruleset;
    }

    public function getTestOnly()
    {
        return $this->testOnly;
    }

    public function setTestOnly($testOnly)
    {
        $this->testOnly = $testOnly;
    }

    /**
     * @param boolean $display_timezone
     */
    public function setDisplayTimezone($display_timezone) {
        $this->display_timezone = $display_timezone;
    }

    /**
     * @return boolean
     */
    public function getDisplayTimezone() {
        return $this->display_timezone;
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
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug(),
            '_locale' => $this->getLocale()
        );
    }

    public function __toString()
    {
        return 'Giveaway => { Id = '.$this->getId().', Name = "'.$this->getName().'", Status = "'.$this->getStatus().'", TestOnly = '.($this->getTestOnly() ? 'True' : 'False').', Type = "'.$this->getGiveawayType().'" }';
    }

    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A giveaway needs an id before it can have a comment thread');
        }

        return self::COMMENT_PREFIX.$this->getId();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getPools()
    {
        return $this->pools;
    }

    public function setPools($value)
    {
        $this->pools = $value;
    }

    public function __call($method, array $arguments = array())
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = call_user_func_array(array($translation, $method), $arguments);
        }

        return $value;
    }

    private function translate(Site $locale = null)
    {
        $currentLocale = $locale ?: $this->getCurrentLocale();

        return $this->translations->filter(function($translation) use($currentLocale) {
            return $translation->getLocale() === $currentLocale;
        })->first();
    }

    public function getName()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getName();
        }

        return $value ?: $this->name;
    }

    public function getContent()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getContent();
        }

        return $value ?: $this->content;
    }

    public function getCurrentLocale()
    {
        return $this->currentLocale ?: $this->defaultLocale;
    }

    public function setCurrentLocale(Site $locale = null)
    {
        $this->currentLocale = $locale;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(GiveawayTranslation $translation)
    {
        $this->translations->add($translation);
        $translation->setTranslatable($this);
    }

    public function setTranslations(Collection $translations)
    {
        foreach ($translations as $translation) {
            $this->addTranslation($translation);
        }
    }

    public function removeTranslation(GiveawayTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * @return string
     */
    public function getRedemptionInstructions()
    {
        return $this->redemptionInstructions;
    }

    /**
     * @param string $redemptionInstructions
     */
    private function setRedemptionInstructions($redemptionInstructions)
    {
        $this->redemptionInstructions = $redemptionInstructions;
    }

    /**
     * Explodes the redemptionInstructions text by new line and removing the prefix:
     *
     * The literal source text (with opening asterisks) looks like this:
     *
     *  * foo
     *  * bar
     *
     * @return array
     */
    public function getRedemptionInstructionsArray()
    {
        $arr = explode(self::REDEMPTION_LINE_PREFIX, $this->getRedemptionInstructions());

        foreach ($arr as $lineNo => $line) {
            // remove trailing whitespace
            $arr[$lineNo] = trim($line);

            // unset the whole dang entry if it's empty
            if (empty($line)) {
                unset($arr[$lineNo]);
            }
        }

        // re-index the array
        $arr = array_values($arr);

        // make sure we have at least 6 entries
        while (count($arr) < 6) {
            $arr[] = '';
        }

        return $arr;
    }

    /**
     * Allows you to set the redemption instructions where each step is
     * an item in an array
     *
     * @param array $instructions
     */
    public function setRedemptionInstructionsArray(array $instructions)
    {
        $str = '';
        foreach ($instructions as $line) {
            // only store the line if it's non-blank
            if ($line) {
                $str .= self::REDEMPTION_LINE_PREFIX . $line."\n";
            }
        }

        $this->setRedemptionInstructions(trim($str));
    }

    /**
     * Returns the redemption instructions array, but without blank lines
     *
     * @return array
     */
    public function getCleanedRedemptionInstructionsArray()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getCleanedRedemptionInstructionsArray();

            if ($value) {
                return $value;
            }
        }

        $cleaned = array();
        foreach ($this->getRedemptionInstructionsArray() as $item) {
            if ($item) {
                $cleaned[] = $item;
            }
        }

        return $cleaned;
    }

    /**
     * Makes sure the redemption instructions are trimmed
     *
     * @ORM\prePersist
     * @ORM\preUpdate
     */
    public function trimRedemptionInstructions()
    {
        $this->setRedemptionInstructions(trim($this->getRedemptionInstructions()));
    }

    /**
     * Add an user
     *
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     */
    public function addUser(GiveawayPool $pool)
    {
        $this->giveawayPools->add($pool);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        if (!$status) {
            return;
        }

        if (!in_array($status, array_keys(self::$validStatuses))) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s" given', $status));
        }

        $this->status = $status;
    }

    /**
     * Returns the "text" for the current status
     *
     * The text is actually just a translation key
     *
     * @return string
     */
    public function getStatusText()
    {
        return self::$validStatuses[$this->getStatus() ?: 'disabled'];
    }

    /**
     * Returns a key-value pair of valid status keys and their text translation
     *
     * Useful in forms
     *
     * @return array
     */
    static public function getValidStatusesMap()
    {
        return self::$validStatuses;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->getStatus() == 'disabled';
    }

    public function isActive()
    {
        return $this->getStatus() == 'active';
    }

    public function setAsActive()
    {
        $this->setStatus('active');
    }

    /**
     * Returns the "active" pool, which is just the first one we find that
     * is indeed active
     *
     * @return \Platformd\GiveawayBundle\Entity\GiveawayPool
     */
    public function getActivePool()
    {
        foreach($this->getPools() as $pool) {
            if ($pool->getIsActive()) {
                return $pool;
            }
        }
    }

    /**
     * Returns the route name to this item's show page
     *
     * @deprecated It's use should be replaced by the LinkableInterface
     * @return string
     */
    public function getShowRouteName()
    {
        return 'giveaway_show';
    }

    /**
     * @return string
     */
    public function getGiveawayType()
    {
        return $this->giveawayType;
    }

    public function giveawayTypeText()
    {
        return self::TYPE_TEXT_PREFIX.$this->getGiveawayType();
    }

    /**
     * @param string $giveawayType
     */
    public function setGiveawayType($giveawayType)
    {
        if ($giveawayType != self::TYPE_KEY_GIVEAWAY && $giveawayType != self::TYPE_MACHINE_CODE_SUBMIT) {
            throw new \InvalidArgumentException(sprintf('Invalid giveaway type "%s" given', $giveawayType));
        }

        $this->giveawayType = $giveawayType;
    }

    /**
     * @return bool
     */
    public function getShowKeys()
    {
        // show the keys if its a traditional key giveaway
        return $this->getGiveawayType() == self::TYPE_KEY_GIVEAWAY && $this->displayRemainingKeysNumber;
    }

    /**
     * Whether or not a user is able to freely register for giveaway keys for this giveaway
     *
     * @return bool
     */
    public function allowKeyFetch()
    {
        return self::TYPE_KEY_GIVEAWAY == $this->getGiveawayType();
    }

    /**
     * Whether or not the user can submit a machine code for this giveaway
     *
     * @return bool
     */
    public function allowMachineCodeSubmit()
    {
        return self::TYPE_MACHINE_CODE_SUBMIT == $this->getGiveawayType();
    }

    static public function getTypeChoices()
    {
        return array(
            self::TYPE_KEY_GIVEAWAY => self::TYPE_TEXT_PREFIX.self::TYPE_KEY_GIVEAWAY,
            self::TYPE_MACHINE_CODE_SUBMIT => self::TYPE_TEXT_PREFIX.self::TYPE_MACHINE_CODE_SUBMIT,
        );
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    public function getLinkableRouteName()
    {
        return 'giveaway_show';
    }

    public function isDisplayRemainingKeysNumber()
    {
        return $this->displayRemainingKeysNumber;
    }

    public function setDisplayRemainingKeysNumber($displayRemainingKeysNumber)
    {
        $this->displayRemainingKeysNumber = $displayRemainingKeysNumber;
    }

    public function setGroup($value)
    {
        $this->group = $value;
    }

    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return bool
     */
    public function getFeatured()
    {
        return $this->featured;
    }

    /**
     * @param bool $value
     */
    public function setFeatured($value)
    {
        $this->featured = $value;
        if($value) {
            $this->featuredAt = new DateTime('now');
        }
    }

    /**
     * @return DateTime
     */
    public function getFeaturedAt()
    {
        return $this->featuredAt;
    }

    /**
     * @param DateTime $value
     */
    public function setFeaturedAt($value)
    {
        $this->featuredAt = $value;
    }

    /**
     * @return Platformd\MediaBundle\Entity\Media
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param Platformd\MediaBundle\Entity\Media $value
     */
    public function setThumbnail($value)
    {
        $this->thumbnail = $value;
    }

    public function getBackgroundImagePath()
    {
        if ($translation = $this->translate()) {
            if ($path = $translation->getBackgroundImagePath()) {
                return $path;
            }
        }

        return $this->backgroundImagePath;
    }

    public function setBackgroundImagePath($backgroundImagePath)
    {
        $this->backgroundImagePath = $backgroundImagePath;
    }

    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    public function setBackgroundImage($backgroundImage)
    {
        $this->backgroundImage = $backgroundImage;
    }

    public function getBackgroundLink($bypassTranslations = false)
    {
        if (!$bypassTranslations && $translation = $this->translate()) {
            if ($link = $translation->getBackgroundLink()) {
                return $link;
            }
        }

        return $this->backgroundLink;
    }

    public function setBackgroundLink($backgroundLink)
    {
        $this->backgroundLink = $backgroundLink;
    }

    public function getBannerImage($bypassTranslations = false)
    {
        if (!$bypassTranslations && $translation = $this->translate()) {
            if ($path = $translation->getBannerImage()) {
                return $path;
            }
        }

        return $this->bannerImage;
    }

    public function setBannerImage($bannerImage)
    {
        $this->bannerImage = $bannerImage;
    }
}
