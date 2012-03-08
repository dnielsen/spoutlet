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
 *      "giveaway"  = "Platformd\GiveawayBundle\Entity\Giveaway",
 *      "sweepstakes"  = "Platformd\SweepstakesBundle\Entity\Sweepstakes"
 * })
 *
 * Special validation on our slug field
 * @AssertUniqueSlug()
 */
abstract class AbstractEvent
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
     * @Assert\NotBlank(message="Please enter a URL string value")
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
     * @ORM\Column(name="locale", type="string", length="2", nullable=false)
     * @Assert\NotBlank
     */
    protected $locale;

    /**
     * @ORM\Column(name="bannerImage", type="string", length=255, nullable=true)
     */
    protected $bannerImage;

    /**
     * @Assert\File(
        maxSize="6000000",
        mimeTypes={"image/png", "image/jpeg", "image/jpg"}
     * )
     */
    protected $bannerImageFile;

    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var datetime $updated
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
        maxSize="6000000",
        mimeTypes={"image/png", "image/jpeg", "image/jpg"}
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
}