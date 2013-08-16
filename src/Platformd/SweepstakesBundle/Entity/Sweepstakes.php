<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Sluggable\Util\Urlizer;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use DateTime;

use Platformd\UserBundle\Entity\User,
    Platformd\TagBundle\Model\TaggableInterface,
    Platformd\SpoutletBundle\Link\LinkableInterface,
    Platformd\SweepstakesBundle\Entity\SweepstakesQuestion;

/**
 * Platformd\SweepstakesBundle\Entity\Sweepstakes
 * @ORM\Table(name="pd_sweepstakes", uniqueConstraints={@ORM\UniqueConstraint(name="slug_unique", columns={"slug"})})
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepstakesRepository")
 *
 * @UniqueEntity(fields={"slug"}, message="sweepstakes.errors.slug_unique")
 * @UniqueEntity(fields={"name"}, message="sweepstakes.errors.name_unique")
 */
class Sweepstakes implements TaggableInterface, LinkableInterface
{
    const COMMENT_PREFIX  = 'sweepstake-';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

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
     private $sites;

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
    private $externalUrl;

    /**
     * @ORM\Column(name="starts_at", type="datetime")
     */
    private $startsAt;

    /**
     * @ORM\Column(name="ends_at", type="datetime")
     */
    private $endsAt;

    /**
     * @ORM\Column(name="hidden", type="boolean")
     */
    private $hidden = false;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    protected $backgroundImage;

    /**
     * @ORM\Column(name="official_rules", type="text", nullable=true)
     */
    private $officialRules;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesEntry", mappedBy="sweepstakes")
     */
    private $entries;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $group = null;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesQuestion", mappedBy="sweepstakes", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $questions;

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

    private $tags;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
        $this->sites   = new ArrayCollection();
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

    public function getEntriesCount() { return count($this->entries); }

    public function getLinkableOverrideUrl()     { return $this->getExternalUrl(); }
    public function getLinkableRouteName()       { return 'sweepstakes_show';  }
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
}
