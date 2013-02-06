<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

use Vich\GeographicalBundle\Annotation as Vich;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Sluggable\Util\Urlizer
;

use Platformd\SpoutletBundle\Entity\Game,
    Platformd\SpoutletBundle\Entity\ContentReport,
    Platformd\SpoutletBundle\Model\ReportableContentInterface,
    Platformd\SpoutletBundle\Link\LinkableInterface,
    Platformd\UserBundle\Entity\User
;

use DateTime;

/**
 * Base Event
 *
 * @ORM\MappedSuperclass
 * @Vich\Geographical
 */
abstract class Event implements ReportableContentInterface, LinkableInterface
{
    const REGISTRATION_ENABLED      = 'REGISTRATION_ENABLED';
    const REGISTRATION_DISABLED     = 'REGISTRATION_DISABLED';
    const REGISTRATION_3RD_PARTY    = 'REGISTRATION_3RDPARTY';

    const DELETED_BY_OWNER  = 'by_owner';
    const DELETED_BY_ADMIN  = 'by_admin';

    static private $validDeletedReasons = array(
        self::DELETED_BY_OWNER,
        self::DELETED_BY_ADMIN,
        ContentReport::DELETED_BY_REPORT,
        ContentReport::DELETED_BY_REPORT_ADMIN,
    );

    /**
     * Event's name
     *
     * @var string $name
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * URL slug for event
     * Only partially automatically set, through setName()
     *
     * @var string $slug
     * @ORM\Column(name="slug", type="string", length=255)
     * @Assert\Regex(pattern="/^[A-Za-z0-9\-]+$/", message="This can only contain letters, numbers and dashes (-)")
     *      Only allow numbers, digits and dashes
     * This should not happen, since it should generate based on name
     */
    protected $slug;

    /**
     * Event description
     *
     * @var string $content
     * @Assert\NotBlank()
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * Event's creator
     *
     * @var User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $user;

    /**
     * Banner Image for event
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $bannerImage;

    /**
     * Event registration option (enabled, disabled, 3rd party)
     *
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name="registration_option", type="string", length=255)
     */
    protected $registrationOption = self::REGISTRATION_ENABLED;

    /**
     * Whether event is published
     *
     * @var boolean $published
     * @ORM\Column(name="published", type="boolean")
     */
    protected $published = false;

    /**
     * Whether event has been approved by Admin
     *
     * @var boolean $approved
     * @ORM\Column(name="approved", type="boolean")
     */
    protected $approved = false;

    /**
     * Whether event is online or physical
     *
     * @var boolean $online
     * @ORM\Column(name="online", type="boolean")
     */
    protected $online = true;

    /**
     * Event starts at
     *
     * @var \DateTime $starts_at
     * @Assert\NotBlank()
     * @ORM\Column(name="starts_at", type="datetime", nullable=true)
     */
    protected $startsAt;

    /**
     * Events ends at
     *
     * @var \DateTime $ends_at
     * @Assert\NotBlank()
     * @ORM\Column(name="ends_at", type="datetime", nullable=true)
     */
    protected $endsAt;

    /**
     * The timezone this event is taking place in
     *
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $timezone = 'UTC';

    /**
     * Whether to display timezone for this event
     *
     * @var boolean $displayTimezone
     * @ORM\Column(name="display_timezone", type="boolean")
     */
    protected $displayTimezone = true;

    /**
     * Game this event relates to
     *
     * @var Game
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $game;

    /**
     * Link to a separate website or URL for this event
     *
     * @var string
     * @Assert\Url
     * @ORM\Column(name="external_url", length="255", nullable=true)
     */
    protected $externalUrl;

    /**
     * Event attendees
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $attendees;

    /**
     * A descriptor, like "CloudCamp Studios" or "Waldorf Astoria"
     *
     * @var string $location
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     */
    protected $location;

    /**
     * The complete address like "1021 Washington Drive, San Francisco, CA United States"
     *
     * @var string $address
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    protected $address;

    /**
     * This gets value from Google Location service
     *
     * @var float
     * @ORM\Column(type="decimal", scale=7, nullable=true)
     */
    protected $latitude;

    /**
     * This gets value from Google Location service
     *
     * @var float
     * @ORM\Column(type="decimal", scale=7, nullable=true)
     */
    protected $longitude;

    /**
     * Created At
     *
     * @var \DateTime $created
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * Updated At
     *
     * @var \DateTime $updated
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $deletedReason;

    /**
     * @var boolean $deleted
     * @ORM\Column(type="boolean")
     */

    private $deleted = false;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="groupEvent")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */
    protected $contentReports;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attendees        = new ArrayCollection();
        $this->contentReports   = new ArrayCollection();
        $this->createdAt        = new DateTime();
    }

    /**
     * @Vich\GeographicalQuery
     *
     * This method returns the full address to query for coordinates.
     */
    public function getFullAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @Vich\GeographicalQuery
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param boolean $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    /**
     * @return boolean
     */
    public function getApproved()
    {
        return $this->approved;
    }

    public function setBannerImage($bannerImage)
    {
        $this->bannerImage = $bannerImage;
    }

    public function getBannerImage()
    {
        return $this->bannerImage;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param boolean $display_timezone
     */
    public function setDisplayTimezone($display_timezone)
    {
        $this->displayTimezone = $display_timezone;
    }

    /**
     * @return boolean
     */
    public function getDisplayTimezone()
    {
        return $this->displayTimezone;
    }

    /**
     * @param \DateTime $endsAt
     */
    public function setEndsAt($endsAt)
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

    public function setExternalUrl($externalUrl)
    {
        $this->externalUrl = $externalUrl;
    }

    public function getExternalUrl()
    {
        return $this->externalUrl;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Game $game
     */
    public function setGame($game)
    {
        $this->game = $game;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Game
     */
    public function getGame()
    {
        return $this->game;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param boolean $online
     */
    public function setOnline($online)
    {
        $this->online = $online;
    }

    /**
     * @return boolean
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * @param boolean $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
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
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param \DateTime $startsAt
     */
    public function setStartsAt($startsAt)
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
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setAttendees($attendees)
    {
        $this->attendees = $attendees;
    }

    public function getAttendees()
    {
        return $this->attendees;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $registrationOption
     */
    public function setRegistrationOption($registrationOption)
    {
        $this->registrationOption = $registrationOption;
    }

    /**
     * @return string
     */
    public function getRegistrationOption()
    {
        return $this->registrationOption;
    }

    public function getDateRangeString()
    {
        $startsAtDate = $this->getStartsAt()->format('M d');
        $startsAtYear = $this->getStartsAt()->format('Y');
        $endsAtDate = $this->getEndsAt()->format('M d');
        $endsAtYear = $this->getEndsAt()->format('Y');

        if ($startsAtYear == $endsAtYear) {
            return ($startsAtDate == $endsAtDate) ? $startsAtDate.', '.$endsAtYear : $startsAtDate.' - '.$endsAtDate.', '.$startsAtYear;
        } else {
            return $startsAtDate.', '.$startsAtYear.' - '.$endsAtDate.', '.$endsAtYear;
        }
    }

    public function getContentReports()
    {
        return $this->contentReports;
    }

    public function setContentReports($contentReports)
    {
        $this->contentReports = $contentReports;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeletedReason($value)
    {
        if ($value && !in_array($value, self::$validDeletedReasons)) {
            throw new \InvalidArgumentException(sprintf('Invalid reason for deletion "%s" given', $value));
        }

        $this->deletedReason = $value;
    }

    public function getDeletedReason()
    {
        return $this->deletedReason;
    }
}
