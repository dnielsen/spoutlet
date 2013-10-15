<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Component\Validator\ExecutionContext;

use Vich\GeographicalBundle\Annotation as Vich;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Sluggable\Util\Urlizer
;

use Platformd\GameBundle\Entity\Game,
    Platformd\SpoutletBundle\Link\LinkableInterface,
    Platformd\UserBundle\Entity\User,
    Platformd\EventBundle\Entity\GlobalEvent,
    Platformd\EventBundle\Entity\GroupEvent,
    Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil,
    Platformd\SearchBundle\Model\IndexableInterface,
    Platformd\TagBundle\Model\TaggableInterface
;

use DateTime,
    DateTimeZone
;

/**
 * Base Event
 *
 * @ORM\MappedSuperclass
 * @Vich\Geographical
 * @Assert\Callback(methods={"externalContentCheck", "validateDateRanges", "validateAddressField", "validateSlug"})
 * @ORM\HasLifecycleCallbacks()
 */
abstract class Event implements LinkableInterface, IndexableInterface, TaggableInterface
{
    const REGISTRATION_ENABLED      = 'REGISTRATION_ENABLED';
    const REGISTRATION_DISABLED     = 'REGISTRATION_DISABLED';
    const REGISTRATION_3RD_PARTY    = 'REGISTRATION_3RDPARTY';

    // overridden in group and global event entities
    const SEARCH_PREFIX             = 'event_';

    /**
     * Event's name
     *
     * @var string $name
     * @Assert\NotBlank(message="Name Required")
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * URL slug for event
     * Only partially automatically set, through setName()
     *
     * @var string $slug
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     * @Assert\Regex(pattern="/^[A-Za-z0-9\-]+$/", message="This can only contain letters, numbers and dashes (-)")
     *      Only allow numbers, digits and dashes
     * This should not happen, since it should generate based on name
     */
    protected $slug;

    /**
     * Event description
     *
     * @var string $content
     * @Assert\NotBlank(message="Content Required")
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
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"all"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $bannerImage;

    /**
     * Event registration option (enabled, disabled, 3rd party)
     *
     * @var string
     * //@Assert\NotBlank(message="Registration Option Required")
     * @ORM\Column(name="registration_option", type="string", length=255)
     */
    protected $registrationOption = self::REGISTRATION_ENABLED;

    /**
     * Whether event is published
     *
     * @var boolean $published
     * @ORM\Column(name="published", type="boolean")
     */
    protected $published = true;

    /**
     * Whether event has been approved by Admin
     *
     * @var boolean $approved
     * @ORM\Column(name="approved", type="boolean")
     */
    protected $approved = false;

    /**
     * Whether the event is active or canceled
     *
     * @var boolean $active
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active = true;

    /**
     * Whether event is online or physical
     *
     * @var boolean $online
     * @ORM\Column(name="online", type="boolean")
     * //Assert\NotNull(message="Required")
     */
    protected $online;

    /**
     * Event starts at
     *
     * @var \DateTime $startsAt
     * @Assert\NotNull(message="Required")
     * @ORM\Column(name="starts_at", type="datetime", nullable=true)
     */
    protected $startsAt;

    /**
     * Events ends at
     *
     * @var \DateTime $endsAt
     * @Assert\NotNull(message="Required")
     * @ORM\Column(name="ends_at", type="datetime", nullable=true)
     */
    protected $endsAt;

    /**
     * The timezone this event is taking place in
     *
     * @var string
     * //Assert\NotBlank(message="Required")
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
     * @ORM\ManyToOne(targetEntity="Platformd\GameBundle\Entity\Game")
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
     * The street section of the address, like "1021 Washington Drive"
     *
     * @var string $address
     * @ORM\Column(name="address1", type="string", length=255, nullable=true)
     */
    protected $address1;

    /**
     * The remaining address, like "San Francisco, CA United States"
     *
     * @var string $address
     * @ORM\Column(name="address2", type="string", length=255, nullable=true)
     */
    protected $address2;

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
     * @ORM\Column(type="datetime", name="created_at")
     */
    protected $createdAt;

    /**
     * Updated At
     *
     * @var \DateTime $updated
     * @ORM\Column(type="datetime", name="updated_at")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $attendeeCount = 0;

    /**
     * @var Platformd\TagBundle\Entity\Tag[]
     *
     */
    private $tags;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\Idea", mappedBy="event", cascade={"remove"})
     */
    protected $ideas;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isVotingActive;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isSubmissionActive;

    /**
     * @ORM\Column(type="string", nullable="true")
     */
    protected $allowedVoters;

    /**
     * @ORM\Column(type="integer")
     */
    protected $currentRound;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attendees    = new ArrayCollection();
        $this->createdAt    = new DateTime();

        $this->startsAt     = new \DateTime('now');
        $this->endsAt       = new \DateTime('now');
        $this->ideas        = new ArrayCollection();
        $this->currentRound = 1;
    }

    /**
     * @Vich\GeographicalQuery
     *
     * This method returns the full address to query for coordinates.
     */
    public function getFullAddress()
    {
        return $this->address1.', '.$this->address2;
    }

    /**
     * @return string
     */
    public function getHtmlFormattedAddress()
    {
        return $this->address1.'<br />'.$this->address2;
    }

    /**
     * @param string $address
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
    }

    /**
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param string $address
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
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

    public function isApproved()
    {
        return $this->getApproved();
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
     * @param \Platformd\GameBundle\Entity\Game $game
     */
    public function setGame($game)
    {
        $this->game = $game;
    }

    /**
     * @return \Platformd\GameBundle\Entity\Game
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
    public function setSlug($slug, $force = false)
    {
        // don't let the slug be blanked out
        // this allows the user to not enter a slug in the form. The slug
        // will be generated from the name, but not overridden by that blank
        // slug value
        if (!$slug && !$force) {
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
    public function setStartsAt($startsAt, $allowPast = false)
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
     * Tries to get a friendly name for the event's timezone
     *
     * @return string
     */
    public function getTimezoneString()
    {
        $dt = new DateTime('now');
        $dt->setTimezone(new DateTimeZone($this->timezone));
        $tz = $dt->format('T');
        $offset = $tz == "GMT" ? "" : ' (GMT '.$dt->format('P').')';

        return $dt->format('T').$offset;
    }

    private function convertDatetimeToTimezone(DateTime $dt)
    {
        $userTimezone = new DateTimeZone($this->getTimezone());
        $offset = $userTimezone->getOffset($dt);

        $timestamp = $dt->format('U') + $offset;

        return DateTime::createFromFormat('U', $timestamp, $userTimezone);
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    public function getLinkableOverrideUrl()
    {
        return $this->externalUrl ?: false;
    }

    /**
     * @param int $attendeeCount
     */
    public function setAttendeeCount($attendeeCount)
    {
        $this->attendeeCount = $attendeeCount;
    }

    /**
     * @return int
     */
    public function getAttendeeCount()
    {
        return $this->attendeeCount;
    }

    /**
     * @param int $increment
     */
    public function updateAttendeeCount($increment = 1)
    {
        $this->attendeeCount += $increment;
    }

    public function externalContentCheck(ExecutionContext $context)
    {
        if ($this instanceof GlobalEvent) {
            $external = $this->externalUrl ? true : false;
        } else {
            $external = $this->getRegistrationOption() == self::REGISTRATION_3RD_PARTY && $this->externalUrl;
        }

        if ($external) {
            if ($this->getContent() == "") {
                $this->setContent('This event is hosted at an external URL.');
            }

            if ($this instanceof GroupEvent) {
                $this->setPrivate(0);
            }
        }
    }

    public function validateDateRanges(ExecutionContext $executionContext)
    {
        if ($this->endsAt < $this->startsAt) {
            $propertyPath = $executionContext->getPropertyPath() . '.endsAt';
            $executionContext->setPropertyPath($propertyPath);

            $executionContext->addViolation(
                "The end date/time must be after the start date/time.",
                array(),
                "endsAt"
            );
        }
    }

    public function isUserAttending($user)
    {
        if (!$user){
            return false;
        }

        foreach ($this->attendees as $attendee) {
            if($attendee->getId() == $user->getId()) {
                return true;
            }
        }

        return false;
    }

    public function validateAddressField(ExecutionContext $executionContext)
    {
        if ($this->online == false) {

            $oldPath = $executionContext->getPropertyPath();

            if ($this->location == null) {
                $propertyPath = $oldPath . '.location';
                $executionContext->setPropertyPath($propertyPath);

                $executionContext->addViolation(
                    "You must enter a location for an In-Person event.",
                    array(),
                    "location"
                );

                $executionContext->setPropertyPath($oldPath);
            }

            if ($this->address1 == null) {
                $propertyPath = $oldPath . '.address1';
                $executionContext->setPropertyPath($propertyPath);

                $executionContext->addViolation(
                    "You must enter a complete address for an In-Person event.",
                    array(),
                    "address1"
                );

                $executionContext->setPropertyPath($oldPath);
            }

             if ($this->address2 == null) {
                $propertyPath = $oldPath . '.address2';
                $executionContext->setPropertyPath($propertyPath);

                $executionContext->addViolation(
                    "You must enter a complete address for an In-Person event.",
                    array(),
                    "address2"
                );

                $executionContext->setPropertyPath($oldPath);
            }
        }
    }

    public function validateSlug(ExecutionContext $executionContext)
    {
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($this->getName());

            if (!$slug) {
                $oldPath = $executionContext->getPropertyPath();
                $propertyPath = $oldPath . '.name';
                $executionContext->setPropertyPath($propertyPath);

                $executionContext->addViolation(
                    "Please enter a valid name for your event.",
                    array(),
                    "name"
                );

                $executionContext->setPropertyPath($oldPath);
            }
        }
    }

    public function getStartsAtUtc()
    {
        if (!$this->startsAt) {
            return null;
        }

        return TzUtil::getUtc($this->startsAt, new \DateTimeZone($this->timezone));
    }

    public function getEndsAtUtc()
    {
        if (!$this->endsAt) {
            return null;
        }

        return TzUtil::getUtc($this->endsAt, new \DateTimeZone($this->timezone));
    }

    public function getSearchTitle()
    {
        return $this->name;
    }

    public function getSearchBlurb()
    {
        return $this->content && !$this->externalUrl ? $this->content : '';
    }

    public function getSearchDate()
    {
        return $this->startsAt;
    }

    public function getDeleteSearchDocument()
    {
        return false == $this->published || false == $this->approved || false == $this->active;
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function getTaggableType()
    {
        $calledClass = get_called_class();
        $type = 'global_event';
        switch ($calledClass) {
            case 'Platformd\EventBundle\Entity\GroupEvent':
                $type = 'group_event';
                break;
        }

        return sprintf('platformd_%s', $type);
    }

    public function getTaggableId()
    {
        return $this->getId();
    }


    public function setIsVotingActive($isVotingActive)
    {
        $this->isVotingActive = $isVotingActive;
        return $this;
    }
    public function getIsVotingActive()
    {
        return $this->isVotingActive;
    }

    public function setIsSubmissionActive($isSubmissionActive)
    {
        $this->isSubmissionActive = $isSubmissionActive;
        return $this;
    }
    public function getIsSubmissionActive()
    {
        return $this->isSubmissionActive;
    }

    public function setAllowedVoters($allowedVoters)
    {
        $this->allowedVoters = $allowedVoters;
    }
    public function getAllowedVoters()
    {
        return $this->allowedVoters;
    }
    public function containsVoter($voter)
    {
        if( strlen($this->allowedVoters) == 0)
            return false;

        $voters = preg_split("/[\s,]+/", trim($this->allowedVoters));
        if (in_array($voter, $voters)) {
            return true;
        }

        return false;
    }

    public function getCurrentRound()
    {
        return $this->currentRound;
    }
    public function setCurrentRound($currentRound)
    {
        $this->currentRound = $currentRound;
    }
    public function getIdeas()
    {
        return $this->ideas;
    }
    public function setIdeas($ideas)
    {
        $this->ideas = $ideas;
    }
}
