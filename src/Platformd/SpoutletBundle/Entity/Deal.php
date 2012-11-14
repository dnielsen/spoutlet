<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\Validator\ExecutionContext;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use DateTimezone;
use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;

/**
 * Platformd\SpoutletBundle\Entity\Deal
 * @ORM\Table(
 *      name="pd_deal",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="slug_unique",
 *              columns={"slug"}
 *          )
 *      }
 * )
 * @UniqueEntity(fields={"slug"}, message="This URL is already used.  If you have left slug blank, this means that an existing deal is already using this deal name.")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\DealRepository")
 */

class Deal implements LinkableInterface
{

    const REDEMPTION_LINE_PREFIX = '* ';
    const STATUS_PUBLISHED       = 'published';
    const STATUS_UNPUBLISHED     = 'unpublished';

    const COMMENT_PREFIX         = 'deal-';

    private static $validStatuses = array(
        self::STATUS_PUBLISHED,
        self::STATUS_UNPUBLISHED
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
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="external_url", length="255", nullable=true)
     */
    private $externalUrl;

     /**
     * @var \Platformd\SpoutletBundle\Entity\Game
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private $game;

    /**
     * @var \DateTime $startsAt
     * @ORM\Column(name="starts_at", type="datetime", nullable=true)
     */
    private $startsAt;

    /**
     * @var \DateTime $endsAt
     * @ORM\Column(name="ends_at", type="datetime", nullable=true)
     */
    private $endsAt;

    /**
     * The timezone this event is taking place in
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var string
     */
    protected $timezone = 'UTC';


    /**
     * The banner image for the deal (950px by 610px)
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $banner;

    /**
     * The large thumbnail for the deal (138px by 83px)
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $thumbnailLarge;

    /**
     * The claim code image for the deal (224px by 43px)
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $claimCodeButton;

    /**
     * The visit website image for the deal (224px by 43px)
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $visitWebsiteButton;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     * @ORM\JoinTable(
     *   name="pd_deal_page_gallery_media",
     *   joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *   inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     */
    protected $mediaGalleryMedias;

    /**
     * Top gradient color value
     * @ORM\Column(name="top_color", type="string", length=255, nullable=true)
     */
    private $topColor;

    /**
     * Bottom gradient color value
     * @ORM\Column(name="bottom_color", type="string", length=255, nullable=true)
     */
    private $bottomColor;

    /**
     *
     * @var OpenGraphOverride
     * @ORM\OneToOne(targetEntity="OpenGraphOverride", cascade={"persist"})
     */
    private $openGraphOverride;

    /**
     * @var string $description
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var text $legalVerbiage
     *
     * @ORM\Column(name="legal_verbiage", type="text", nullable=true)
     */
    private $legalVerbiage;

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
     * website url
     * @ORM\Column(name="website_url", type="string", length=255, nullable=true)
     */
    private $websiteUrl;

    /**
     * The published/unpublished/archived field
     *
     * @var string
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     * @Assert\NotBlank(message="error.select_status")
     */
    private $status;

    /**
     * One to Many with DealPool
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\DealPool", mappedBy="deal")
     */
    protected $dealPools;

    /**
     * Holds the "many" locales relationship
     *
     * Don't set this directly, instead set "locales" directly, and a listener
     * will take care of properly creating the DealLocale relationship
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="DealLocale", orphanRemoval=true, mappedBy="deal")
     */
    private $dealLocales;

    private $locales;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_deal_site")
     */
    private $sites;

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
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset", cascade={"persist"})
     */
    private $ruleset;

    /**
     * @ORM\Column(name="sitified_at", type="datetime", nullable="true")
     */
    protected $sitifiedAt;

    public function __construct()
    {
        $this->mediaGalleryMedias = new ArrayCollection();
        $this->dealPools = new ArrayCollection();
        $this->dealLocales = new ArrayCollection();
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
        # this allows slug to be left blank and set elsewhere without it getting overridden here
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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($name);

            $this->setSlug($slug);
        }

        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
        $this->game = $game;
    }

    /**
     * @param \DateTime $startsAt
     */
    public function setStartsAt(\DateTime $startsAt = null)
    {
        $this->startsAt = $startsAt;
    }


    /**
     * @return \DateTime
     */
    public function getStartsAt()
    {
       return $this->startsAt;
    }

    /**
     * @return \DateTime
     */
    public function getStartsAtUtc()
    {
        if (!$this->getStartsAt()) {
            return null;
        }

        return TzUtil::getUtc($this->getStartsAt(), new \DateTimeZone($this->getTimezone()));
    }

    /**
     * @return \DateTime
     */
    public function getEndsAtUtc()
    {
        if (!$this->getEndsAt()) {
            return null;
        }

        return TzUtil::getUtc($this->getEndsAt(), new \DateTimeZone($this->getTimezone()));
    }

     /**
     * @param \DateTime $endsAt
     */
    public function setEndsAt(\DateTime $endsAt = null)
    {
        $this->endsAt = $endsAt;
    }

    /**
     * @return \DateTime
     */
    public function getEndsAt()
    {
        return $this->endsAt;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone ? $this->timezone : 'UTC';
    }

    /**
     * Tries to get a friendly name for the event's timezone
     *
     * @return string
     */
    public function getTimezoneString()
    {
        $dtz = new \DateTimeZone($this->getTimezone());

        $offset = $dtz->getOffset(new DateTime());

        return isset(self::$timzoneCommonNames[$offset]) ? self::$timzoneCommonNames[$offset] : $dtz->getName();
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
    public function getLinkableRouteName()
    {
        return 'deal_show';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug()
        );
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $banner
     */
    public function setBanner(Media $banner = null)
    {
        $this->banner = $banner;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getThumbnailLarge()
    {
        return $this->thumbnailLarge;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $thumbnailLarge
     */
    public function setThumbnailLarge(Media $thumbnailLarge = null)
    {
        $this->thumbnailLarge = $thumbnailLarge;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getClaimCodeButton()
    {
        return $this->claimCodeButton;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $claimCodeButton
     */
    public function setClaimCodeButton(Media $claimCodeButton = null)
    {
        $this->claimCodeButton = $claimCodeButton;
    }

    // visitWebsiteButton

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getVisitWebsiteButton()
    {
        return $this->visitWebsiteButton;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $visitWebsiteButton
     */
    public function setVisitWebsiteButton(Media $visitWebsiteButton = null)
    {
        $this->visitWebsiteButton = $visitWebsiteButton;
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

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
        $cleaned = array();
        foreach ($this->getRedemptionInstructionsArray() as $item) {
            if ($item) {
                $cleaned[] = $item;
            }
        }

        return $cleaned;
    }

    /**
     * Set websiteUrl
     *
     * @param string $websiteUrl
     */
    public function setWebsiteUrl($websiteUrl)
    {
        $this->websiteUrl = $websiteUrl;
    }

    /**
     * Get websiteUrl
     *
     * @return string
     */
    public function getWebsiteUrl()
    {
        return $this->websiteUrl;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMediaGalleryMedias()
    {
        return $this->mediaGalleryMedias;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        if ($status && !in_array($status, self::$validStatuses)) {
            throw new \InvalidArgumentException(sprintf('Invalid status passed: "%s"', $status));
        }

        $this->status = $status;
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
        return $this->getStatus() == self::STATUS_PUBLISHED;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $pools
     */
    public function setDealPools($pools)
    {
        $this->dealPools = $pools;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDealPools()
    {
        return $this->dealPools;
    }

    public function getActivePool()
    {
        foreach($this->getDealPools() as $pool) {
            if ($pool->getIsActive()) {
                return $pool;
            }
        }
    }

    /**
     * @static
     * @return array
     */
    static public function getValidStatuses()
    {
        return self::$validStatuses;
    }

    public function getLocales()
    {
        $this->locales;
    }

    public function setLocales(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDealLocales()
    {
        return $this->dealLocales;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $dealLocales
     */
    public function setDealLocales($dealLocales)
    {
        $this->dealLocales = $dealLocales;
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
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getRuleset()
    {
        return $this->ruleset;
    }

    public function setRuleset($ruleset)
    {
        $this->ruleset = $ruleset;
    }

    /**
     * @return boolean
     */
    public function isActive() {

        if (!$this->isPublished()) {
            return false;
        }

        if ($this->getStartsAt() && $this->getEndsAt()) {
            // we have a start and an end
            return TzUtil::isNowBetween($this->getStartsAt(), $this->getEndsAt(), new \DateTimeZone($this->getTimezone()));
        } elseif (!$this->getStartsAt()) {
            // we have no start, but we do have an end
            return !$this->hasExpired();
        } else {
            // we have a start, but no end, so we just need to see if it's started
            TzUtil::isNowAfter($this->getStartsAt(), new \DateTimeZone($this->getTimezone()));
        }
    }

    public function hasExpired() {
        // it can never expire without an end date
        if (!$this->getEndsAt()) {
            return false;
        }

        return TzUtil::isNowAfter($this->getEndsAt(), new \DateTimeZone($this->getTimezone()));
    }

    /**
     * Used to return the commenting thread id that should be used for this deal
     */
    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A deal needs an id before it can have a comment thread');
        }

        return self::COMMENT_PREFIX.$this->getId();
    }

    /**
     * Set topColor
     *
     * @param string $topColor
     */
    public function setTopColor($topColor)
    {
        $this->topColor = $topColor;
    }

    /**
     * Get topColor
     *
     * @return string
     */
    public function getTopColor()
    {
        return $this->topColor;
    }

    /**
     * Set bottomColor
     *
     * @param string $bottomColor
     */
    public function setBottomColor($bottomColor)
    {
        $this->bottomColor = $bottomColor;
    }

    /**
     * Get bottomColor
     *
     * @return string
     */
    public function getBottomColor()
    {
        return $this->bottomColor;
    }
}
