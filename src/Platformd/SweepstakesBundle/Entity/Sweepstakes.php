<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Sluggable\Util\Urlizer;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\Validator\ExecutionContext
;

use DateTime;

use Platformd\UserBundle\Entity\User,
    Platformd\TagBundle\Model\TaggableInterface,
    Platformd\SpoutletBundle\Link\LinkableInterface,
    Platformd\SweepstakesBundle\Entity\SweepstakesQuestion,
    Platformd\MediaBundle\Entity\Media
;

/**
 * Platformd\SweepstakesBundle\Entity\Sweepstakes
 * @ORM\Table(name="pd_sweepstakes", indexes={@ORM\index(name="event_type_idx", columns={"event_type"})}, uniqueConstraints={@ORM\UniqueConstraint(name="slug_unique", columns={"slug"})})
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepstakesRepository")
 *
 * @Assert\Callback(methods={"validatePromoCodeFields"})
 * @UniqueEntity(fields={"slug"}, message="sweepstakes.errors.slug_unique")
 * @UniqueEntity(fields={"name"}, message="sweepstakes.errors.name_unique")
 */
class Sweepstakes implements TaggableInterface, LinkableInterface
{
    const COMMENT_PREFIX  = 'sweepstake-';
    const FORMS_S3_PREFIX = 'promo_code/';

    const SWEEPSTAKES_TYPE_SWEEPSTAKES = 'sweepstakes';
    const SWEEPSTAKES_TYPE_PROMO_CODE  = 'promocode';

    static protected $validTypes = array(
        self::SWEEPSTAKES_TYPE_SWEEPSTAKES,
        self::SWEEPSTAKES_TYPE_PROMO_CODE,
    );

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * Only partially automatically set, through setName()
     *
     * @ORM\Column(name="slug", type="string", length=255)
     * @Assert\Regex(pattern="/^[A-Za-z0-9\-]+$/", message="This can only contain letters, numbers and dashes (-)")
     *      Only allow numbers, digits and dashes
     * This should not happen, since it should generate based on name
     */
    protected $slug;

    /**
     * @ORM\Column(name="published", type="boolean")
     */
    protected $published = false;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_sweepstakes_site")
     */
     protected $sites;

     /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $timezone = 'UTC';

    /**
     * @Assert\Url
     * @ORM\Column(name="external_url", length="255", nullable=true)
     */
    protected $externalUrl;

    /**
     * @Assert\DateTime()
     * @Assert\NotBlank()
     * @ORM\Column(name="starts_at", type="datetime")
     */
    protected $startsAt;

    /**
     * @Assert\DateTime()
     * @Assert\NotBlank()
     * @ORM\Column(name="ends_at", type="datetime")
     */
    protected $endsAt;

    /**
     * @ORM\Column(name="hidden", type="boolean")
     */
    protected $hidden = false;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    protected $backgroundImage;

    /**
     * @ORM\Column(name="official_rules", type="text", nullable=true)
     */
    protected $officialRules;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesEntry", mappedBy="sweepstakes")
     */
    protected $entries;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $group = null;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesQuestion", mappedBy="sweepstakes", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $questions;

    /**
     * @ORM\Column(name="test_only", type="boolean", nullable=true)
     *
     */
    protected $testOnly = false;

    /**
     * @ORM\Column(name="meta_description", type="string", length=150, nullable=true)
     *
     */
    protected $metaDescription = false;

    /**
     * @ORM\Column(name="has_optional_checkbox", type="boolean")
     *
     */
    protected $hasOptionalCheckbox = false;

    /**
     * @ORM\Column(name="optional_checkbox_label", type="string", length=255, nullable=true)
     *
     */
    protected $optionalCheckboxLabel;

    /**
     * @ORM\Column(name="event_type", type="string", length=20)
     *
     */
    protected $eventType = self::SWEEPSTAKES_TYPE_SWEEPSTAKES;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\PromoCodeContestCode", mappedBy="contest", cascade={"persist"})
     */
    protected $winningCodes;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\PromoCodeContestConsolationCode", mappedBy="contest", cascade={"persist"})
     */
    protected $consolationCodes;

    /**
     * @ORM\Column(name="winning_codes_count", type="integer", nullable=true)
     */
    protected $winningCodesCount;

    /**
     * @ORM\Column(name="consolation_codes_count", type="integer", nullable=true)
     */
    protected $consolationCodesCount;

    protected $tags;

    /**
     * @Assert\File(maxSize="6000000")
     */
    protected $winningCodesFile;

    /**
     * @Assert\File(maxSize="6000000")
     */
    protected $consolationCodesFile;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $affidavit;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $w9form;

    /**
     * @ORM\Column(name="winner_message", type="text", nullable="true")
     */
    protected $winnerMessage;

    /**
     * @ORM\Column(name="loser_message", type="text", nullable=true)
     */
    protected $loserMessage;

    /**
     * @ORM\Column(name="backup_loser_message", type="text", nullable=true)
     */
    protected $backupLoserMessage;

    public function __construct()
    {
        $this->entries   = new ArrayCollection();
        $this->questions = new ArrayCollection();
        $this->sites     = new ArrayCollection();
    }

    public function getId() { return $this->id; }

    public function getName() { return $this->name; }
    public function setName($value)
    {
        $this->name = $value;

        // sets the slug, but only if it's blank
        // this is not meant to be smart enough to guarantee correct uniqueness
        // that will happen with validation
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($value);

            $this->setSlug($slug);
        }
    }

    public function getSlug() { return $this->slug; }
    public function setSlug($value)
    {
        if (!$value) {
            return;
        }

        $this->slug = $value;
    }

    public function getPublished()       { return $this->published; }
    public function setPublished($value) { $this->published = $value; }

    public function getSites()       { return $this->sites; }
    public function setSites($value) { $this->sites = $value; }

    public function getCreated()                { return $this->created; }
    public function setCreated(DateTime $value) { $this->created = $value; }

    public function getUpdated()                { return $this->updated; }
    public function setUpdated(DateTime $value) { $this->updated = $value; }

    public function getTimezone()
    {
        return $this->timezone ? $this->timezone : 'UTC';
    }

    public function setTimezone($value) { $this->timezone = $value; }

    public function getExternalUrl()       { return $this->externalUrl; }
    public function setExternalUrl($value) { $this->externalUrl = $value; }

    public function getStartsAt()       { return $this->startsAt; }
    public function setStartsAt($value) { $this->startsAt = $value; }

    public function getEndsAt()       { return $this->endsAt; }
    public function setEndsAt($value) { $this->endsAt = $value; }

    public function getHidden()       { return $this->hidden; }
    public function setHidden($value) { $this->hidden = $value; }

    public function getContent()       { return $this->content; }
    public function setContent($value) { $this->content = $value; }

    public function getBackgroundImage()       { return $this->backgroundImage; }
    public function setBackgroundImage($value) { $this->backgroundImage = $value; }

    public function getEntries()       { return $this->entries; }
    public function setEntries($value) { $this->entries = $value; }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function setTags($value) { $this->tags = $value; }

    public function getGroup()       { return $this->group; }
    public function setGroup($value) { $this->group = $value; }

    public function getOfficialRules()       { return $this->officialRules;  }
    public function setOfficialRules($value) { $this->officialRules = $value; }

    public function getQuestions()       { return $this->questions;  }

    public function setQuestions($value) {
        foreach($value as $question){
            $this->addSweepstakesQuestion($question);
        }
    }

    public function getTestOnly()       { return $this->testOnly;  }
    public function setTestOnly($value) { $this->testOnly = $value; }

    public function getMetaDescription()       { return $this->metaDescription;  }
    public function setMetaDescription($value) { $this->metaDescription = $value; }

    public function getHasOptionalCheckbox()       { return $this->hasOptionalCheckbox;  }
    public function setHasOptionalCheckbox($value) { $this->hasOptionalCheckbox = $value; }

    public function getOptionalCheckboxLabel()       { return $this->optionalCheckboxLabel;  }
    public function setOptionalCheckboxLabel($value) { $this->optionalCheckboxLabel = $value; }

    public function setEventType($value)
    {
        if ($value && !in_array($value, self::$validTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid Event Type "%s" given', $value));
        }

        $this->eventType = $value;
    }

    public function getEventType()       { return $this->eventType; }

    public function setWinningCodes($value) { $this->winningCodes = $value; }
    public function getWinningCodes()       { return $this->winningCodes; }

    public function setConsolationCodes($value) { $this->consolationCodes = $value; }
    public function getConsolationCodes()       { return $this->consolationCodes; }

    public function setWinningCodesCount($value) { $this->winningCodesCount = $value; }
    public function getWinningCodesCount()       { return $this->winningCodesCount ?: 0; }

    public function setConsolationCodesCount($value) { $this->consolationCodesCount = $value; }
    public function getConsolationCodesCount()       { return $this->consolationCodesCount ?: 0; }

    public function incrementWinningCodesCount($amount = 1)
    {
        $this->winningCodesCount = (null === $this->winningCodesCount ? $amount : $this->winningCodesCount + $amount);
    }

    public function incrementConsolationCodesCount($amount = 1)
    {
        $this->consolationCodesCount = (null === $this->consolationCodesCount ? $amount : $this->consolationCodesCount + $amount);
    }

    public function getWinningCodesFile()       { return $this->winningCodesFile;  }
    public function setWinningCodesFile($value) { $this->winningCodesFile = $value; }

    public function getConsolationCodesFile()       { return $this->consolationCodesFile;  }
    public function setConsolationCodesFile($value) { $this->consolationCodesFile = $value; }

    public function getAffidavit()
    {
        if (null === $this->affidavit) {
            $this->affidavit = new Media(true);
            return $this->affidavit;
        }

        $this->affadavit->setIgnoreMime(true);
        return $this->affidavit;
    }

    public function setAffidavit($value) { $this->affidavit = $value; }

    public function getW9form()
    {
        if (null === $this->w9form) {
            $this->w9form = new Media(true);
            return $this->w9form;
        }

        $this->w9form->setIgnoreMime(true);
        return $this->w9form;
    }

    public function setW9form($value) { $this->w9form = $value; }

    public function getWinnerMessage()       { return $this->winnerMessage;  }
    public function setWinnerMessage($value) { $this->winnerMessage = $value; }

    public function getLoserMessage()       { return $this->loserMessage;  }
    public function setLoserMessage($value) { $this->loserMessage = $value; }

    public function getBackupLoserMessage()       { return $this->backupLoserMessage;  }
    public function setBackupLoserMessage($value) { $this->backupLoserMessage = $value; }

    public function addWinningCode(PromoCodeContestCode $code)
    {
        if (!$this->winningCodes) {
            $this->winningCodes = new ArrayCollection();
            $this->winningCodesCount = 0;
        }

        $code->setContest($this);
        $this->winningCodes->add($code);
        $this->winningCodesCount++;
    }

    public function addConsolationCode(PromoCodeContestConsolationCode $code)
    {
        if (!$this->consolationCodes) {
            $this->consolationCodes = new ArrayCollection();
            $this->consolationCodesCount = 0;
        }

        $code->setContest($this);
        $this->consolationCodes->add($code);
        $this->consolationCodesCount++;
    }

    public function getEntriesCount() { return count($this->entries); }

    public function getLinkableOverrideUrl()     { return $this->getExternalUrl(); }

    public function getLinkableRouteName()
    {
        return $this->eventType == self::SWEEPSTAKES_TYPE_SWEEPSTAKES ? 'sweepstakes_show' : 'promo_code_contest_show';
    }

    public function getLinkableRouteParameters() { return array('slug' => $this->getSlug()); }

    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A sweepstakes needs an id before it can have a comment thread');
        }

        return self::COMMENT_PREFIX.$this->getId();
    }

    public function getTaggableType() { return 'platformd_sweepstakes'; }
    public function getTaggableId() { return $this->getId(); }

    public function addSweepstakesQuestion(SweepstakesQuestion $question)
    {
        $question->setSweepstakes($this);
        $this->questions->add($question);
    }

    public function removeSweepstakesQuestion(SweepstakesQuestion $question)
    {
        $this->questions->removeElement($question);
    }

    public function isCurrentlyOpen()
    {
        $now = time();

        if (!$this->getStartsAt()) {
            return false;
        }

        $start = $this->getStartsAt()->format('U');
        $end   = $this->getEndsAt() ? $this->getEndsAt()->format('U') : null;

        if ($now < $start || ($end && $now > $end)) {
            return false;
        }

        return true;
    }

    public function hasStarted()
    {
        $now = time();

        if (!$this->getStartsAt()) {
            return true;
        }

        $start = $this->getStartsAt()->format('U');

        if ($now < $start) {
            return false;
        }

        return true;
    }

    public function isFinished()
    {
        $now = time();

        if (!$this->getEndsAt()) {
            return false;
        }

        $end   = $this->getEndsAt() ? $this->getEndsAt()->format('U') : null;

        if ($end && $now > $end) {
            return true;
        }

        return false;
    }

    static public function getValidTypes()
    {
        return self::$validTypes;
    }

    public function validatePromoCodeFields(ExecutionContext $executionContext)
    {
        if ($this->getEventType() == self::SWEEPSTAKES_TYPE_SWEEPSTAKES) {
            return;
        }

        $winnerMessage = $this->getWinnerMessage();
        $loserMessage = $this->getLoserMessage();

        if (empty($winnerMessage)) {
            $this->addError($executionContext, 'winnerMessage', 'Please fill in the winner message.');
        }

        if (empty($loserMessage)) {
            $this->addError($executionContext, 'loserMessage', 'Please fill in the loser message.');
        }

        if (!$this->getWinningCodesCount() && null === $this->getWinningCodesFile()) {
            $this->addError($executionContext, 'winningCodesFile', 'Please attach a .csv file containing the winning code(s).');
        }

        if (!$this->getConsolationCodesCount() && null === $this->getConsolationCodesFile()) {
            $this->addError($executionContext, 'consolationCodesFile', 'Please attach a .csv file containing the consolation code(s).');
        }

        if (null === $this->getAffidavit()->getFileName() && null === $this->getAffidavit()->getFileObject()) {
            $this->addError($executionContext, 'affidavit.fileObject', 'Please attach an Affidavit file.');
        }

        if (null === $this->getW9Form()->getFileName() && null === $this->getW9Form()->getFileObject()) {
            $this->addError($executionContext, 'w9form.fileObject', 'Please attach a W9 form.');
        }
    }

    private function addError(ExecutionContext $executionContext, $path, $message)
    {
        $oldPath = $executionContext->getPropertyPath();
        $executionContext->setPropertyPath($oldPath.'.'.$path);

        $executionContext->addViolation(
            $message,
            array(),
            $path
        );

        $executionContext->setPropertyPath($oldPath);

        return $executionContext;
    }
}
