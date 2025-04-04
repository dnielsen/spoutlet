<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection
;

use Symfony\Component\Validator\Constraints as Assert;

use Vich\GeographicalBundle\Annotation as Vich;

use Platformd\GroupBundle\Entity\Group,
    Platformd\SpoutletBundle\Entity\Site,
    Platformd\EventBundle\Validator\GroupEventUniqueSlug as AssertUniqueSlug,
    Platformd\SpoutletBundle\Entity\ContentReport,
    Platformd\SpoutletBundle\Model\ReportableContentInterface,
    Platformd\SpoutletBundle\Link\LinkableInterface,
    Platformd\SearchBundle\Model\IndexableInterface
;

/**
 * Platformd\EventBundle\Entity\GroupEvent
 *
 * @ORM\Table(name="group_event")
 * @ORM\Entity
 * @AssertUniqueSlug()
 * @Vich\Geographical(on="update")
 */
class GroupEvent extends Event implements ReportableContentInterface, LinkableInterface, IndexableInterface
{
    const DELETED_BY_OWNER  = 'by_owner';
    const DELETED_BY_ADMIN  = 'by_admin';

    const SEARCH_PREFIX     = 'group_event_';

    static private $validDeletedReasons = array(
        self::DELETED_BY_OWNER,
        self::DELETED_BY_ADMIN,
        ContentReport::DELETED_BY_REPORT,
        ContentReport::DELETED_BY_REPORT_ADMIN,
    );

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Whether event is private or public
     * Private events will not display on listing pages, public will
     *
     * @var boolean $private
     * @ORM\Column(name="private", type="boolean")
     * @Assert\NotNull(message="Required")
     */
    protected $private = false;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\EventBundle\Entity\GroupEventTranslation", mappedBy="translatable", cascade={"all"})
     */
    protected $translations;

    /**
     * @var Site
     */
    protected $currentLocale;

    protected $defaultLocale = 'en';

    /**
     * Groups the event pertains to
     *
     * @var Group
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $group;

    /**
     * Sites this event belongs to - override default sites defined in group if set
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="group_events_sites")
     */
    protected $sites;

    /**
     * Event attendees
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable(name="group_events_attendees")
     * @ORM\OrderBy({"username" = "ASC"})
     */
    protected $attendees;

    /**
     * Event RSVP actions
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\EventBundle\Entity\GroupEventRsvpAction", mappedBy="event", cascade={"persist"})
     */
    protected $rsvpActions;


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
     * @ORM\ManyToMany(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove", "persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $rotatorImages;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\SponsorRegistry", mappedBy="event", cascade={"persist", "remove"})
     */
    protected $sponsorRegistrations;

    /**
     * Constructor
     */
    public function __construct(Group $group)
    {
        $this->group            = $group;
        $this->translations     = new ArrayCollection();
        $this->sites            = new ArrayCollection();
        $this->contentReports   = new ArrayCollection();
        $this->rsvpActions      = new ArrayCollection();
        $this->rotatorImages    = new ArrayCollection();
        $this->sponsorRegistrations = new ArrayCollection();

        foreach ($this->getGroup()->getSites() as $site) {
            $this->sites->add($site);
        }

        parent::__construct();
    }

    private function translate(Site $locale = null)
    {
        $currentLocale = $locale ?: $this->getCurrentLocale();

        return $this->translations->filter(function($translation) use($currentLocale) {
            return $translation->getLocale() === $currentLocale;
        })->first();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Overrides default to add translation
     *
     * @return string
     */
    public function getName()
    {

        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getName();
        }

        return $value ?: $this->name;
    }

    /**
     * Overrides default to add translation
     *
     * @return string
     */
    public function getContent()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getContent();
        }

        return $value ?: $this->content;
    }

    /**
     * Overrides default to add translation
     *
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getBannerImage()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getBannerImage();
        }

        return $value ?: $this->bannerImage;
    }

    /**
     * @param \Platformd\GroupBundle\Entity\Group $group
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return \Platformd\GroupBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $private
     */
    public function setPrivate($private)
    {
        $this->private = $private;
    }

    /**
     * @return boolean
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $sites
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * Content type for the purposes of content reporting
     *
     * @return string
     */
    public function getContentType()
    {
        return 'GroupEvent';
    }

    public function setTranslations($translations)
    {
        foreach ($translations as $translation) {
            $this->addTranslation($translation);
        }
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(GroupEventTranslation $translation)
    {
        $this->translations->add($translation);
        $translation->setTranslatable($this);
    }

    public function removeTranslation(GroupEventTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    public function getThreadId()
    {
        return 'group-event-'.$this->getId();
    }

    public function getLinkableRouteName()
    {
        return 'group_event_view';
    }

    public function getLinkableRouteParameters()
    {
        return array(
            'eventId' => $this->id,
            'groupSlug' => $this->group->getSlug(),
        );
    }

    public function getCurrentLocale()
    {
        return $this->currentLocale ?: $this->defaultLocale;
    }

    public function setCurrentLocale(Site $locale = null)
    {
        $this->currentLocale = $locale;
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

    public function getReportThreshold()
    {
        return 3;
    }

    public function setRsvpActions($value)
    {
        $this->rsvpActions = $value;
    }

    public function getRsvpActions()
    {
        return $this->rsvpActions;
    }

    public function getSearchEntityType()
    {
        return 'group_event';
    }

    public function getSearchFacetType()
    {
        return 'event';
    }

    public function getDeleteSearchDocument()
    {
        return false == $this->published || false == $this->approved || false == $this->active || $this->deleted || $this->private;
    }

    public function getSearchId()
    {
        return self::SEARCH_PREFIX.$this->id;
    }

    public function getRotatorImages()
    {
        return $this->rotatorImages;
    }

    public function setRotatorImages($value)
    {
        $this->rotatorImages = $value;
    }
}
