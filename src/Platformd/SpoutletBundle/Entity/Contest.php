<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Sluggable\Util\Urlizer;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\ExecutionContext;

use DateTime;
use DateTimezone;

use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\TagBundle\Model\TaggableInterface;


/**
 * Platformd\SpoutletBundle\Entity\Contest
 * @ORM\Table(
 *      name="pd_contest",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="slug_unique",
 *              columns={"slug"}
 *          )
 *      }
 * )
 * @UniqueEntity(fields={"slug"}, message="This URL is already used.  If you have left slug blank, this means that an existing deal is already using this deal name.")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\ContestRepository")
 * @Assert\Callback(methods={"validateDateRanges"})
 */
class Contest implements LinkableInterface, TaggableInterface
{
    const REDEMPTION_LINE_PREFIX = '* ';

    const STATUS_PUBLISHED       = 'published';
    const STATUS_UNPUBLISHED     = 'unpublished';
    const STATUS_ARCHIVED        = 'archived';

    private static $validStatuses = array(
        self::STATUS_PUBLISHED,
        self::STATUS_UNPUBLISHED,
        self::STATUS_ARCHIVED,
    );

    private static $validCategories = array(
        'image',
        'video',
        'group',
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
     * @var string $name
     *
     * @Assert\NotNull
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string $category
     *
     * @ORM\Column(name="category", type="string")
     */
    private $category;

    /**
     * @var \Platformd\GameBundle\Entity\Game
     * @ORM\ManyToOne(targetEntity="Platformd\GameBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private $game;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_contest_site")
     * @Assert\NotNull
     */
    private $sites;

    /**
     * @var datetime $submission_start
     * @Assert\NotNull
     * @ORM\Column(name="submission_start", type="datetime")
     */
    private $submissionStart;

    /**
     * @var datetime $submission_end
     * @Assert\NotNull
     * @ORM\Column(name="submission_end", type="datetime")
     */
    private $submissionEnd;

    /**
     * @var datetime $voting_start
     * @Assert\NotNull
     * @ORM\Column(name="voting_start", type="datetime")
     */
    private $votingStart;

    /**
     * @var datetime $voting_end
     * @Assert\NotNull
     * @ORM\Column(name="voting_end", type="datetime")
     */
    private $votingEnd;

    /**
     * @var datetime $voting_end
     * @Assert\NotNull
     * @ORM\Column(name="voting_end_utc", type="datetime")
     */
    private $votingEndUtc;

    /**
     * @var string $timezone
     *
     * @ORM\Column(name="timezone", type="string", length=255)
     */
    private $timezone = 'UTC';

    /**
     * The banner image for the contest (950px by 610px)
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $banner;

    /**
     * @var text $rules
     * @Assert\NotNull
     * @ORM\Column(name="rules", type="text")
     */
    private $rules;

    /**
     * @var text $entry_instructions
     * @Assert\NotNull
     * @ORM\Column(name="entry_instructions", type="text")
     */
    private $entryInstructions;

    /**
     * @var text $vote_instructions
     * @Assert\NotNull
     * @ORM\Column(name="vote_instructions", type="text")
     */
    private $voteInstructions;

    /**
     * @var text $redemption_instructions
     *
     * @ORM\Column(name="redemption_instructions", type="text")
     */
    private $redemptionInstructions;

    /**
     * @var integer $max_entries
     *
     * @ORM\Column(name="max_entries", type="integer")
     */
    private $maxEntries;

    /**
     *
     * @var openGraphOverride
     * @ORM\OneToOne(targetEntity="OpenGraphOverride", cascade={"persist"})
     */
    private $openGraphOverride;

    /**
     * The published/unpublished/archived field
     *
     * @var string
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    private $status;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset", cascade={"persist"})
     */
    private $ruleset;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContestEntry", mappedBy="contest")
     */
    protected $entries;

    /**
     * The published/unpublished/archived field
     *
     * @var string
     * @ORM\Column(name="winners", type="array", nullable=true)
     */
    protected $winners;

    /**
     * @ORM\Column(name="test_only", type="boolean", nullable=true)
     *
     */
    protected $testOnly = false;

    /**
     * @var Platformd\TagBundle\Entity\Tag[]
     *
     */
    private $tags;

    /**
     * @ORM\Column(name="hidden", type="boolean")
     */
    private $hidden = false;

    public function __construct()
    {
        $this->sites = new ArrayCollection();
        $this->entries = new ArrayCollection();
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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
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

    public function setCategory($category)
    {
        if (!in_array($category, self::$validCategories)) {
            throw new \InvalidArgumentException(sprintf('Invalid category "%s" given', $category));
        }

        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setGame($game)
    {
        $this->game = $game;
    }

    public function getGame()
    {
        return $this->game;
    }

    /**
     * Set sites
     *
     * @param array $sites
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    /**
     * Get sites
     *
     * @return array
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * Set submissionStart
     *
     * @param datetime $submissionStart
     */
    public function setSubmissionStart($submissionStart)
    {
        $this->submissionStart = $submissionStart;
    }

    /**
     * Get submissionStart
     *
     * @return datetime
     */
    public function getSubmissionStart()
    {
        return $this->submissionStart;
    }

    public function getSubmissionStartUtc()
    {
        if (!$this->getSubmissionStart()) {
            return null;
        }

        return TzUtil::getUtc($this->getSubmissionStart(), new \DateTimeZone($this->getTimezone()));
    }

    public function getSubmissionStartTz()
    {
        if (!$this->getSubmissionStart()) {
            return null;
        }

        return TzUtil::getDtMergedWithTz($this->getSubmissionStart(), new \DateTimeZone($this->getTimezone()));
    }

    /**
     * Set submissionEnd
     *
     * @param datetime $submissionEnd
     */
    public function setSubmissionEnd($submissionEnd)
    {
        $this->submissionEnd = $submissionEnd;
    }

    /**
     * Get submissionEnd
     *
     * @return datetime
     */
    public function getSubmissionEnd()
    {
        return $this->submissionEnd;
    }

    public function getSubmissionEndUtc()
    {
        if (!$this->getSubmissionEnd()) {
            return null;
        }

        return TzUtil::getUtc($this->getSubmissionEnd(), new \DateTimeZone($this->getTimezone()));
    }

    public function getSubmissionEndTz()
    {
        if (!$this->getSubmissionEnd()) {
            return null;
        }

        return TzUtil::getDtMergedWithTz($this->getSubmissionEnd(), new \DateTimeZone($this->getTimezone()));
    }

    /**
     * Set votingStart
     *
     * @param datetime $votingStart
     */
    public function setVotingStart($votingStart)
    {
        $this->votingStart = $votingStart;
    }

    /**
     * Get votingStart
     *
     * @return datetime
     */
    public function getVotingStart()
    {
        return $this->votingStart;
    }

    public function getVotingStartUtc()
    {
        if (!$this->getVotingStart()) {
            return null;
        }

        return TzUtil::getUtc($this->getVotingStart(), new \DateTimeZone($this->getTimezone()));
    }

    public function getVotingStartTz() {
        if (!$this->getVotingStart()) {
            return null;
        }

        return TzUtil::getDtMergedWithTz($this->getVotingStart(), new \DateTimeZone($this->getTimezone()));
    }

    /**
     * Set votingEnd
     *
     * @param datetime $votingEnd
     */
    public function setVotingEnd($votingEnd)
    {
        $this->votingEnd = $votingEnd;
        $this->setVotingEndUtc(TzUtil::getUtc($votingEnd, new \DateTimeZone($this->getTimezone())));
    }

    /**
     * Get votingEnd
     *
     * @return datetime
     */
    public function getVotingEnd()
    {
        return $this->votingEnd;
    }

    public function setVotingEndUtc($value)
    {
        $this->votingEndUtc = $value;
    }

    public function getVotingEndUtc()
    {
        return $this->votingEndUtc;
    }

    public function getVotingEndTz()
    {
        if (!$this->getVotingEnd()) {
            return null;
        }

        return TzUtil::getDtMergedWithTz($this->getVotingEnd(), new \DateTimeZone($this->getTimezone()));
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Get timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone ? $this->timezone : 'UTC';
    }

    /**
     * @return \DateTime
     */
    public function getDateTimeUtc(DateTime $datetime)
    {
        return TzUtil::getUtc($datetime, new \DateTimeZone($this->getTimezone()));
    }

    private function convertDatetimeToTimezone(DateTime $dt)
    {
        $userTimezone = new DateTimeZone($this->getTimezone());
        $offset = $userTimezone->getOffset($dt);

        $timestamp = $dt->format('U') + $offset;

        return DateTime::createFromFormat('U', $timestamp, $userTimezone);
    }

    public function setBanner($banner)
    {
        $this->banner = $banner;
    }

    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * Set rules
     *
     * @param text $rules
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    /**
     * Get rules
     *
     * @return text
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Set entryInstructions
     *
     * @param text $entryInstructions
     */
    public function setEntryInstructions($entryInstructions)
    {
        $this->entryInstructions = $entryInstructions;
    }

    /**
     * Get entryInstructions
     *
     * @return text
     */
    public function getEntryInstructions()
    {
        return $this->entryInstructions;
    }

    /**
     * Set voteInstructions
     *
     * @param text $voteInstructions
     */
    public function setVoteInstructions($voteInstructions)
    {
        $this->voteInstructions = $voteInstructions;
    }

    /**
     * Get voteInstructions
     *
     * @return text
     */
    public function getVoteInstructions()
    {
        return $this->voteInstructions;
    }

    /**
     * Set redemptionInstructions
     *
     * @param text $redemptionInstructions
     */
    public function setRedemptionInstructions($redemptionInstructions)
    {
        $this->redemptionInstructions = $redemptionInstructions;
    }

    /**
     * Get redemptionInstructions
     *
     * @return text
     */
    public function getRedemptionInstructions()
    {
        return $this->redemptionInstructions;
    }

    /**
     * Set maxEntries
     *
     * @param integer $maxEntries
     */
    public function setMaxEntries($maxEntries)
    {
        $this->maxEntries = $maxEntries;
    }

    /**
     * Get maxEntries
     *
     * @return integer
     */
    public function getMaxEntries()
    {
        return $this->maxEntries;
    }

    /**
     * @return openGraphOverride
     */
    public function getopenGraphOverride()
    {
        return $this->openGraphOverride;
    }

    /**
     * @param OpenGraphOverride $openGraphOverride
     */
    public function setopenGraphOverride(OpenGraphOverride $openGraphOverride = null)
    {
        $this->openGraphOverride = $openGraphOverride;
    }

    /**
     * Set status
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        if (!in_array($status, self::$validStatuses)) {
            throw new \InvalidArgumentException(sprintf('Invalid status passed: "%s"', $status));
        }

        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getRuleset()
    {
        return $this->ruleset;
    }

    public function setRuleset($ruleset)
    {
        $this->ruleset = $ruleset;
    }

    public function getEntries()
    {
        return $this->entries;
    }

    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    public function getWinners()
    {
        return $this->winners;
    }

    public function setWinners($winners)
    {
        $this->winners = $winners;
    }

    public function getTestOnly()
    {
        return $this->testOnly;
    }

    public function setTestOnly($testOnly)
    {
        $this->testOnly = $testOnly;
    }

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


    public static function getValidStatuses()
    {
        return self::$validStatuses;
    }

    public static function getValidCategories()
    {
        return self::$validCategories;
    }

    /**
     * If there is a set URL that should be used without doing anything else, return it here
     *
     * @return string
     */
    public function getLinkableOverrideUrl()
    {
        return false;
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    public function  getLinkableRouteName()
    {
        return 'contest_show';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function  getLinkableRouteParameters()
    {
        return array(
            'id' => $this->getId(),
            'slug' => $this->getSlug(),
        );
    }

    public function validateDateRanges(ExecutionContext $executionContext)
    {
        // error if submissionEnd or votingEnd datetime values are before their respective start datetimes

        if ($this->submissionEnd > $this->submissionStart && $this->votingEnd > $this->votingStart) {
            return;
        }

        if ($this->submissionEnd < $this->submissionStart) {
            $propertyPath = $executionContext->getPropertyPath() . '.submissionEnd';
            $executionContext->setPropertyPath($propertyPath);

            $executionContext->addViolation(
                "The submission end date/time must be after the start date/time",
                array(),
                "submissionEnd"
            );

            return;
        }

        if ($this->votingEnd < $this->votingStart) {
            $propertyPath = $executionContext->getPropertyPath() . '.votingEnd';
            $executionContext->setPropertyPath($propertyPath);

            $executionContext->addViolation(
                "The voting end date/time must be after the start date/time",
                array(),
                "votingEnd"
            );
        }

    }

    public function isFinished()
    {
        return $this->getVotingEndUtc() < new \DateTime('now');
    }

    public function isVotable()
    {
        return $this->getVotingStartUtc() < new \DateTime('now');
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function getTaggableType()
    {
        return 'platformd_contest';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }

    public function getHidden()
    {
        return $this->hidden;
    }

    public function setHidden($value)
    {
        $this->hidden = $value;
    }
}
