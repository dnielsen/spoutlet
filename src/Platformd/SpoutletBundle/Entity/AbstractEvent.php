<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use DateTime;
use DateTimezone;
use Symfony\Component\HttpFoundation\File\File;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Util\Urlizer;
use Platformd\SpoutletBundle\Validator\AbstractEventUniqueSlug as AssertUniqueSlug;
use Platformd\GameBundle\Entity\Game as Game;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\GroupBundle\Entity\Group;

/**
 * We create a unique index on the slug-discr-site combination
 * @ORM\Table(
 *      name="event",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="slug_unique",
 *              columns={"slug", "discr", "locale"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\AbstractEventRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "event"     = "Platformd\SpoutletBundle\Entity\Event",
 *      "sweepstakes"  = "Platformd\SweepstakesBundle\Entity\Sweepstakes"
 * })
 *
 * Special validation on our slug field
 * @AssertUniqueSlug()
 */
abstract class AbstractEvent implements LinkableInterface
{
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
     * @ORM\JoinTable(name="pd_event_site")
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
     * @ORM\Column(name="sitified_at", type="datetime", nullable="true")
     */
    protected $sitifiedAt;

    /**
     * @ORM\Column(name="test_only", type="boolean", nullable=true)
     *
     */
    protected $testOnly = false;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $group = null;

    public function __construct()
    {
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
     * Returns the route name to this item's show page
     *
     * @return string
     */
    abstract public function getShowRouteName();

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
     * Get content
     *
     * @return text
     */
    public function getContent()
    {
        return $this->content;
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


    public function getBannerImage()
    {
        return $this->bannerImage;
    }

    public function setBannerImage($bannerImage)
    {
        $this->bannerImage = $bannerImage;
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

    public function setGroup($value)
    {
        $this->group = $value;
    }

    public function getGroup()
    {
        return $this->group;
    }
}
