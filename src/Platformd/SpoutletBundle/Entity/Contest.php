<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;
use DateTimezone;
use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

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
 */
class Contest implements LinkableInterface
{
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
     * @var \Platformd\SpoutletBundle\Entity\Game
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private $game;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_contest_site")
     */
    private $sites;

    /**
     * @var datetime $submission_start
     *
     * @ORM\Column(name="submission_start", type="datetime")
     */
    private $submission_start;

    /**
     * @var datetime $submission_end
     *
     * @ORM\Column(name="submission_end", type="datetime")
     */
    private $submission_end;

    /**
     * @var datetime $voting_start
     *
     * @ORM\Column(name="voting_start", type="datetime")
     */
    private $voting_start;

    /**
     * @var datetime $voting_end
     *
     * @ORM\Column(name="voting_end", type="datetime")
     */
    private $voting_end;

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
     *
     * @ORM\Column(name="rules", type="text")
     */
    private $rules;

    /**
     * @var text $entry_instructions
     *
     * @ORM\Column(name="entry_instructions", type="text")
     */
    private $entry_instructions;

    /**
     * @var text $vote_instructions
     *
     * @ORM\Column(name="vote_instructions", type="text")
     */
    private $vote_instructions;

    /**
     * @var text $redemption_instructions
     *
     * @ORM\Column(name="redemption_instructions", type="text")
     */
    private $redemption_instructions;

    /**
     * @var integer $max_entries
     *
     * @ORM\Column(name="max_entries", type="integer")
     */
    private $max_entries;

    /**
     *
     * @var OpenGraphOverride
     * @ORM\OneToOne(targetEntity="OpenGraphOverride", cascade={"persist"})
     */
    private $openGraphOverRide;

    /**
     * The published/unpublished/archived field
     *
     * @var string
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    private $status;

    public function __construct()
    {
        $this->sites = new ArrayCollection();
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
        if ($slug == "" || !$slug) {
            $slug = Urlizer::urlize($this->getName());
            $this->setSlug($slug);
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
            throw new \InvalidArgumentException(sprintf('Invalid gallery category "%s" given', $category));
        }

        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set game_id
     *
     * @param integer $gameId
     */
    public function setGameId($gameId)
    {
        $this->game_id = $gameId;
    }

    /**
     * Get game_id
     *
     * @return integer
     */
    public function getGameId()
    {
        return $this->game_id;
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
     * Set submission_start
     *
     * @param datetime $submissionStart
     */
    public function setSubmissionStart($submissionStart)
    {
        $this->submission_start = $submissionStart;
    }

    /**
     * Get submission_start
     *
     * @return datetime
     */
    public function getSubmissionStart()
    {
        return $this->submission_start;
    }

    /**
     * Set submission_end
     *
     * @param datetime $submissionEnd
     */
    public function setSubmissionEnd($submissionEnd)
    {
        $this->submission_end = $submissionEnd;
    }

    /**
     * Get submission_end
     *
     * @return datetime
     */
    public function getSubmissionEnd()
    {
        return $this->submission_end;
    }

    /**
     * Set voting_start
     *
     * @param datetime $votingStart
     */
    public function setVotingStart($votingStart)
    {
        $this->voting_start = $votingStart;
    }

    /**
     * Get voting_start
     *
     * @return datetime
     */
    public function getVotingStart()
    {
        return $this->voting_start;
    }

    /**
     * Set voting_end
     *
     * @param datetime $votingEnd
     */
    public function setVotingEnd($votingEnd)
    {
        $this->voting_end = $votingEnd;
    }

    /**
     * Get voting_end
     *
     * @return datetime
     */
    public function getVotingEnd()
    {
        return $this->voting_end;
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

    /**
     * Set banner
     *
     * @param string $banner
     */
    public function setBanner($banner)
    {
        $this->banner = $banner;
    }

    /**
     * Get banner
     *
     * @return string
     */
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
     * Set entry_instructions
     *
     * @param text $entryInstructions
     */
    public function setEntryInstructions($entryInstructions)
    {
        $this->entry_instructions = $entryInstructions;
    }

    /**
     * Get entry_instructions
     *
     * @return text
     */
    public function getEntryInstructions()
    {
        return $this->entry_instructions;
    }

    /**
     * Set vote_instructions
     *
     * @param text $voteInstructions
     */
    public function setVoteInstructions($voteInstructions)
    {
        $this->vote_instructions = $voteInstructions;
    }

    /**
     * Get vote_instructions
     *
     * @return text
     */
    public function getVoteInstructions()
    {
        return $this->vote_instructions;
    }

    /**
     * Set redemption_instructions
     *
     * @param text $redemptionInstructions
     */
    public function setRedemptionInstructions($redemptionInstructions)
    {
        $this->redemption_instructions = $redemptionInstructions;
    }

    /**
     * Get redemption_instructions
     *
     * @return text
     */
    public function getRedemptionInstructions()
    {
        return $this->redemption_instructions;
    }

    /**
     * Set max_entries
     *
     * @param integer $maxEntries
     */
    public function setMaxEntries($maxEntries)
    {
        $this->max_entries = $maxEntries;
    }

    /**
     * Get max_entries
     *
     * @return integer
     */
    public function getMaxEntries()
    {
        return $this->max_entries;
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
     * Set status
     *
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
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
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

    /**
     * @static
     * @return array
     */
    static public function getValidStatuses()
    {
        return self::$validStatuses;
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
}
