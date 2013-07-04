<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Platformd\GameBundle\Entity\Game;
use Platformd\SpoutletBundle\Entity\OpenGraphOverride;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Model\CommentableInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use DateTimezone;
use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;
use Platformd\SearchBundle\Model\IndexableInterface;
use Platformd\TagBundle\Model\TaggableInterface;

/**
 * Platformd\GiveawayBundle\Entity\Deal
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
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\DealRepository")
 */

class Deal implements LinkableInterface, CommentableInterface, IndexableInterface, TaggableInterface
{
    const REDEMPTION_LINE_PREFIX = '* ';
    const STATUS_PUBLISHED       = 'published';
    const STATUS_UNPUBLISHED     = 'unpublished';

    const COMMENT_PREFIX         = 'deal-';
    const SEARCH_PREFIX          = 'deal_';

    private static $validStatuses = array(
        self::STATUS_PUBLISHED,
        self::STATUS_UNPUBLISHED
    );

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

     /**
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Url
     * @ORM\Column(name="external_url", length="255", nullable=true)
     */
    private $externalUrl;

     /**
     * @ORM\ManyToOne(targetEntity="Platformd\GameBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private $game;

    /**
     * @ORM\Column(name="starts_at", type="datetime", nullable=true)
     */
    private $startsAt;

    /**
     * @ORM\Column(name="ends_at", type="datetime", nullable=true)
     */
    private $endsAt;

    /**
     * The timezone this event is taking place in
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $timezone = 'UTC';

    /**
     * The banner image for the deal (950px by 610px)
     *
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $banner;

    /**
     * The large thumbnail for the deal (138px by 83px)
     *
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $thumbnailLarge;

    /**
     * The claim code image for the deal (224px by 43px)
     *
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $claimCodeButton;

    /**
     * The visit website image for the deal (224px by 43px)
     *
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $visitWebsiteButton;

    /**
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
     *
     * @ORM\Column(name="top_color", type="string", length=255, nullable=true)
     */
    private $topColor;

    /**
     * Bottom gradient color value
     *
     * @ORM\Column(name="bottom_color", type="string", length=255, nullable=true)
     */
    private $bottomColor;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\OpenGraphOverride", cascade={"persist"})
     */
    private $openGraphOverride;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
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
     */
    protected $redemptionInstructions;

    /**
     * @ORM\Column(name="website_url", type="string", length=255, nullable=true)
     */
    private $websiteUrl;

    /**
     * The published/unpublished/archived field
     *
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     * @Assert\NotBlank(message="error.select_status")
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\DealPool", mappedBy="deal")
     */
    protected $pools;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_deal_site")
     */
    private $sites;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
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

    /**
     * @ORM\Column(name="test_only", type="boolean", nullable=true)
     */
    protected $testOnly = false;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
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
     * @var Platformd\TagBundle\Entity\Tag[]
     *
     */
    private $tags;

    public function __construct()
    {
        $this->mediaGalleryMedias = new ArrayCollection();
        $this->pools              = new ArrayCollection();
        $this->sites              = new ArrayCollection();
    }

    public function __toString() {
        return 'Deal => { Id = '.$this->getId().', Name = "'.$this->getName().'", Status = "'.$this->getStatus().'", TestOnly = '.($this->getTestOnly() ? 'True' : 'False').' }';
    }

    public function setSitifiedAt($sitifiedAt)
    {
        $this->sitifiedAt = $sitifiedAt;
    }

    public function getSitifiedAt()
    {
        return $this->sitifiedAt;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSlug($slug)
    {
        # this allows slug to be left blank and set elsewhere without it getting overridden here
        if (!$slug) {
            return;
        }

        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setName($name)
    {
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($name);

            $this->setSlug($slug);
        }

        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setExternalUrl($externalUrl) {
        $this->externalUrl = $externalUrl;
    }

    public function getExternalUrl() {
        return $this->externalUrl;
    }

    public function getGame()
    {
        return $this->game;
    }

    public function setGame(Game $game)
    {
        $this->game = $game;
    }

    public function setStartsAt(\DateTime $startsAt = null)
    {
        $this->startsAt = $startsAt;
    }

    public function getStartsAt()
    {
       return $this->startsAt;
    }

    public function getStartsAtUtc()
    {
        if (!$this->getStartsAt()) {
            return null;
        }

        return TzUtil::getUtc($this->getStartsAt(), new \DateTimeZone($this->getTimezone()));
    }

    public function getEndsAtUtc()
    {
        if (!$this->getEndsAt()) {
            return null;
        }

        return TzUtil::getUtc($this->getEndsAt(), new \DateTimeZone($this->getTimezone()));
    }

    public function setEndsAt(\DateTime $endsAt = null)
    {
        $this->endsAt = $endsAt;
    }

    public function getEndsAt()
    {
        return $this->endsAt;
    }

    public function getLinkableOverrideUrl()
    {
        return $this->getExternalUrl();
    }

    public function getLinkableRouteName()
    {
        return 'deal_show';
    }

    public function getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug()
        );
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getBanner()
    {
        return $this->banner;
    }

    public function setBanner(Media $banner = null)
    {
        $this->banner = $banner;
    }

    public function getThumbnailLarge()
    {
        return $this->thumbnailLarge;
    }

    public function setThumbnailLarge(Media $thumbnailLarge = null)
    {
        $this->thumbnailLarge = $thumbnailLarge;
    }

    public function getClaimCodeButton()
    {
        return $this->claimCodeButton;
    }

    public function setClaimCodeButton(Media $claimCodeButton = null)
    {
        $this->claimCodeButton = $claimCodeButton;
    }

    public function getVisitWebsiteButton()
    {
        return $this->visitWebsiteButton;
    }

    public function setVisitWebsiteButton(Media $visitWebsiteButton = null)
    {
        $this->visitWebsiteButton = $visitWebsiteButton;
    }

    public function getOpenGraphOverride()
    {
        return $this->openGraphOverride;
    }

    public function setOpenGraphOverride(OpenGraphOverride $openGraphOverride = null)
    {
        $this->openGraphOverride = $openGraphOverride;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setLegalVerbiage($legalVerbiage)
    {
        $this->legalVerbiage = $legalVerbiage;
    }

    public function getLegalVerbiage()
    {
        return $this->legalVerbiage;
    }

    public function getRedemptionInstructions()
    {
        return $this->redemptionInstructions;
    }

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

    public function setWebsiteUrl($websiteUrl)
    {
        $this->websiteUrl = $websiteUrl;
    }

    public function getWebsiteUrl()
    {
        return $this->websiteUrl;
    }

    public function getMediaGalleryMedias()
    {
        return $this->mediaGalleryMedias;
    }

    public function setStatus($status)
    {
        if ($status && !in_array($status, self::$validStatuses)) {
            throw new \InvalidArgumentException(sprintf('Invalid status passed: "%s"', $status));
        }

        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isPublished()
    {
        return $this->getStatus() == self::STATUS_PUBLISHED;
    }

    public function setPools($pools)
    {
        $this->pools = $pools;
    }

    public function getPools()
    {
        return $this->pools;
    }

    public function getActivePool()
    {
        foreach($this->getPools() as $pool) {
            if ($pool->getIsActive()) {
                return $pool;
            }
        }

        return null;
    }

    static public function getValidStatuses()
    {
        return self::$validStatuses;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

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

    public function getTestOnly()
    {
        return $this->testOnly;
    }

    public function setTestOnly($testOnly)
    {
        $this->testOnly = $testOnly;
    }

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
            return TzUtil::isNowAfter($this->getStartsAt(), new \DateTimeZone($this->getTimezone()));
        }
    }

    public function hasExpired() {
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

    public function setTopColor($topColor)
    {
        $this->topColor = $topColor;
    }

    public function getTopColor()
    {
        return $this->topColor;
    }

    public function setBottomColor($bottomColor)
    {
        $this->bottomColor = $bottomColor;
    }

    public function getBottomColor()
    {
        return $this->bottomColor;
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

    public function getSearchEntityType()
    {
        return 'deal';
    }

    public function getSearchFacetType()
    {
        return 'deal';
    }

    public function getSearchId()
    {
        return self::SEARCH_PREFIX.$this->id;
    }

    public function getSearchTitle()
    {
        return $this->name;
    }

    public function getSearchBlurb()
    {
        return $this->description ?: '';
    }

    public function getSearchDate()
    {
        return $this->startsAt;
    }

    public function getDeleteSearchDocument()
    {
        return !$this->isPublished();
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function getTaggableType()
    {
        return 'platformd_deal';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }
}
