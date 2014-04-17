<?php

namespace Platformd\GroupBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Platformd\SpoutletBundle\Entity\Location;
use Platformd\MediaBundle\Entity\Media;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Util\Urlizer;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Site;
use Platformd\GroupBundle\Entity\GroupApplication;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Symfony\Component\Validator\ExecutionContext;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Platformd\GroupBundle\Validator\GroupSlugCollision;
use Platformd\TagBundle\Model\TaggableInterface;
use Platformd\SpoutletBundle\Entity\GalleryMedia;

use Doctrine\ORM\Mapping as ORM;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\SearchBundle\Model\IndexableInterface;

use Platformd\IdeaBundle\Entity\EntrySetRegistry,
    Platformd\IdeaBundle\Entity\EntrySetScopeable;

/**
 * Platformd\GroupBundle\Entity\Group
 *
 * @ORM\Table(name="pd_groups")
 * @ORM\Entity(repositoryClass="Platformd\GroupBundle\Entity\GroupRepository")
 * @UniqueEntity(fields={"name"}, message="This group name is already used.")
 * @UniqueEntity(fields={"slug"}, message="This group url is already used.")
 * @Assert\Callback(methods={"locationRequiredCallBack"})
 * @GroupSlugCollision()
 * @ORM\HasLifecycleCallbacks()
 */
class Group implements LinkableInterface, ReportableContentInterface, IndexableInterface, TaggableInterface, EntrySetScopeable
{
    const GROUP_CATEGORY_LABEL_PREFIX  = 'platformd.groups.category.';
    const DELETED_BY_OWNER  = 'by_owner';
    const DELETED_BY_ADMIN  = 'by_admin';
    const DELETED_BY_REPORT = 'REPORTED_PENDING_INVESTIGATION';
    const DELETED_BY_REPORT_ADMIN = 'REPORTED_AND_REMOVED_BY_ADMIN';

    const CAT_TOPIC = 'topic';
    const CAT_LOCATION = 'location';

    static private $validCategories = array(
        self::CAT_TOPIC,
        self::CAT_LOCATION,
    );

    static private $validDeletedReasons = array(
        self::DELETED_BY_OWNER,
        self::DELETED_BY_ADMIN,
        self::DELETED_BY_REPORT,
        self::DELETED_BY_REPORT_ADMIN,
    );

    const COMMENT_PREFIX = 'group-';
    const SEARCH_PREFIX  = 'group_';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string $name
     * @Assert\NotNull(message="Required")
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string $category
     * @Assert\NotNull(message="Required")
     * @ORM\Column(name="category", type="string", length=50)
     */
    private $category;

    /**
     * @var text $description
     * @Assert\NotNull(message="Required")
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @ORM\Column(name="deletedReason", type="string", length=50, nullable=true)
     */
    private $deletedReason;

    /**
     * @var text $howToJoin
     * @ORM\Column(name="howToJoin", type="text", nullable=true)
     */
    private $howToJoin;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @var boolean $isPublic
     * @Assert\NotNull
     * @ORM\Column(name="isPublic", type="boolean")
     */
    private $isPublic = true;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $backgroundImage;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $groupAvatar;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $thumbNail;

    /**
     * @var \Platformd\SpoutletBundle\Entity\Location
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\Location", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $location;

     /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_group_site")
     */
    private $sites;

    /**
     * @var boolean $allLocales
     * @ORM\Column(name="allLocales", type="boolean")
     */
    private $allLocales = false;

    /**
     * @var boolean $deleted
     * @ORM\Column(name="deleted", type="boolean")
     */

    private $deleted = false;

    /**
     * The person who uploaded this media
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User", mappedBy="pdGroups")
     * @ORM\JoinTable(name="pd_groups_members")
     */
    private $members;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GroupBundle\Entity\GroupApplication", mappedBy="group", cascade={"persist"})
     */
    private $applications;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GroupBundle\Entity\GroupNews", mappedBy="group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $newsArticles;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\VideoBundle\Entity\YoutubeVideo", mappedBy="groups")
     */
    private $videos;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\GalleryMedia", mappedBy="groups")
     */
    private $images;

    /**
     * @var boolean $deleted
     * @ORM\Column(type="boolean")
     */
    private $discussionsEnabled = true;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\GroupBundle\Entity\GroupDiscussion", mappedBy="group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $discussions;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\GroupBundle\Entity\GroupMembershipAction", mappedBy="group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $membershipActions;

    /**
    * @ORM\OneToMany(targetEntity="Platformd\GroupBundle\Entity\GroupMembershipAction", mappedBy="group", cascade={"persist"})
    * @ORM\JoinColumn(onDelete="SET NULL")
    */
    private $userMembershipActions;

    /**
     * @var boolean $featured
     * @ORM\Column(name="featured", type="boolean")
     */

    private $featured = false;

    /**
     * @var \DateTime $featuredAt
     *
     * @ORM\Column(name="featured_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="featured", value="true")
     */
    protected $featuredAt;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */
    protected $contentReports;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $facebookLikesUpdatedAt;

    /**
     * @ORM\Column(type="bigint")
     */
    protected $facebookLikes = 0;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\Deal", mappedBy="group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $deals;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\EventBundle\Entity\GroupEvent", mappedBy="group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $events;

    /**
     * @var Platformd\TagBundle\Entity\Tag[]
     *
     */
    private $tags;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\IdeaBundle\Entity\EntrySetRegistry", cascade={"persist"})
     */
    protected $entrySetRegistration;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\SponsorRegistry", mappedBy="group", cascade={"persist", "remove"})
     */
    protected $sponsorRegistrations;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\RegistrationField", mappedBy="group", cascade={"persist", "remove"})
     */
    protected $registrationFields;


    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group", inversedBy="childGroups")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $parentGroup;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GroupBundle\Entity\Group", mappedBy="parentGroup")
     */
    protected $childGroups;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\HtmlPage", mappedBy="group")
     */
    protected $htmlPages;


    public function __construct()
    {
        $this->sites                    = new ArrayCollection();
        $this->members                  = new ArrayCollection();
        $this->applications             = new ArrayCollection();
        $this->userMembershipActions    = new ArrayCollection();
        $this->contentReports           = new ArrayCollection();
        $this->deals                    = new ArrayCollection();
        $this->videos                   = new ArrayCollection();
        $this->events                   = new ArrayCollection();
        $this->sponsorRegistrations     = new ArrayCollection();
        $this->registrationFields       = new ArrayCollection();
        $this->childGroups              = new ArrayCollection();
        $this->htmlPages                = new ArrayCollection();
    }

    public function __toString() {
        return 'Group => { Id = '.$this->id.', Name = "'.$this->name.'" }';
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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        # this allows slug to be left blank and set elsewhere without it getting overridden here
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

    public function setCategory($category)
    {
        if (!in_array($category, self::$validCategories)) {
            throw new \InvalidArgumentException(sprintf('Invalid group category "%s" given', $category));
        }

        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
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

    public static function getValidCategories()
    {
        return self::$validCategories;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set howToJoin
     *
     * @param text $howToJoin
     */
    public function setHowToJoin($howToJoin)
    {
        $this->howToJoin = $howToJoin;
    }

    /**
     * Get howToJoin
     *
     * @return text
     */
    public function getHowToJoin()
    {
        return $this->howToJoin;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;
    }

    /**
     * Get isPublic
     *
     * @return boolean
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set allLocales
     *
     * @param boolean $allLocales
     */
    public function setAllLocales($allLocales)
    {
        $this->allLocales = $allLocales;
    }

    /**
     * Get allLocales
     *
     * @return boolean
     */
    public function getAllLocales()
    {
        return $this->allLocales;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $backgroundImage
     */
    public function setBackgroundImage(Media $backgroundImage = null)
    {
        $this->backgroundImage = $backgroundImage;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getGroupAvatar()
    {
        return $this->groupAvatar;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $groupAvatar
     */
    public function setGroupAvatar(Media $groupAvatar = null)
    {
        $this->groupAvatar = $groupAvatar;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getThumbNail()
    {
        return $this->thumbNail;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $thumbNail
     */
    public function setThumbNail(Media $thumbNail = null)
    {
        $this->thumbNail = $thumbNail;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Location $location
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMembers()
    {
        return $this->members;
    }

     /**
     * @param \Doctrine\Common\Collections\ArrayCollection $members
     */
    public function setMembers($members)
    {
        $this->members = $members;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getApplications()
    {
        return $this->applications;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $applications
     */
    public function setApplications($applications)
    {
        $this->applications = $applications;
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

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getNewsArticles()
    {
        return $this->newsArticles;
    }

     /**
     * @param \Doctrine\Common\Collections\ArrayCollection $newsArticles
     */
    public function setNewsArticles($newsArticles)
    {
        $this->newsArticles = $newsArticles;
    }

    public function getVideos()
    {
        return $this->videos;
    }

    public function setVideos($value)
    {
        $this->videos = $value;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages($value)
    {
        $this->images = $value;
    }

    public function getMembershipActions()
    {
        return $this->membershipActions;
    }

    public function setMembershipActions($value)
    {
        $this->membershipActions = $value;
    }

    public function getContentReports()
    {
        return $this->contentReports;
    }

    public function setContentReports($value)
    {
        $this->contentReports = $value;
    }

    public function getContentType() {
        return "Group";
    }

    public function isVisibleOnSite($site) {

        if (!$site) {
            return false;
        }

        $isGlobal         = $this->allLocales;
        $isAllowedForSite = $this->sites && $this->sites->contains($site);

        return $isGlobal || $isAllowedForSite;
    }

    public function getUserMembershipActions() {
        return $this->userMembershipActions;
    }

    public function setUserMembershipActions($value) {
        $this->userMembershipActions = $value;
    }

    public function getFeatured()
    {
        return $this->featured;
    }

    public function setFeatured($featured)
    {
        $this->featured = $featured;
    }

    public function getFeaturedAt()
    {
        return $this->featuredAt;
    }

    public function setFeaturedAt($featuredAt)
    {
        $this->featuredAt = $featuredAt;
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
        return 'group_show';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function  getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug(),
        );
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $owner
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Used to return the commenting thread id that should be used for this group
     */
    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A group needs an id before it can have a comment thread');
        }

        return self::COMMENT_PREFIX.$this->getId();
    }

    public function locationRequiredCallBack(ExecutionContext $executionContext)
    {
        if ($this->getCategory() != 'location') {
            return;
        }

        $location = $this->getLocation();

        if ($location && $location->getCity() && strlen($location->getCity()) > 1) {
            return;
        }

        $propertyPath = $executionContext->getPropertyPath().'.location.city';
        $executionContext->setPropertyPath($propertyPath);

        $executionContext->addViolation(
            'Required',
            array(),
            null
        );
    }

    public function setDiscussions($discussions)
    {
        $this->discussions = $discussions;
    }

    public function getDiscussions()
    {
        return $this->discussions;
    }

    /**
     * @param boolean $discussionsEnabled
     */
    public function setDiscussionsEnabled($discussionsEnabled)
    {
        $this->discussionsEnabled = $discussionsEnabled;
    }

    /**
     * @return boolean
     */
    public function getDiscussionsEnabled()
    {
        return $this->discussionsEnabled;
    }

    /**
     * Get facebookLikesUpdatedAt.
     *
     * @return facebookLikesUpdatedAt.
     */
    public function getFacebookLikesUpdatedAt()
    {
        return $this->facebookLikesUpdatedAt;
    }

    /**
     * Set facebookLikesUpdatedAt.
     *
     * @param facebookLikesUpdatedAt the value to set.
     */
    public function setFacebookLikesUpdatedAt(\DateTime $facebookLikesUpdatedAt)
    {
        $this->facebookLikesUpdatedAt = $facebookLikesUpdatedAt;
    }

    /**
     * Get facebookLikes.
     *
     * @return facebookLikes.
     */
    public function getFacebookLikes()
    {
        return $this->facebookLikes;
    }

    /**
     * Set facebookLikes.
     *
     * @param facebookLikes the value to set.
     */
    public function setFacebookLikes($facebookLikes)
    {
        $this->facebookLikes = $facebookLikes;
        $this->facebookLikesUpdatedAt = new \DateTime;
    }

    public function getClass()
    {
        return get_class($this);
    }

    public function getLeftMemberCount()
    {
        return $this->getMembershipActions()->filter(function($x) {
            return
                $x->getCreatedAt() >= new \DateTime('-30 days') &&
                $x->getAction() == GroupMembershipAction::ACTION_LEFT;
        })
        ->count();
    }

    public function getNewMemberCount()
    {
        return $this->getMembershipActions()->filter(function($x) {
            return
                    $x->getCreatedAt() >= new \DateTime('-30 days') &&
                    ($x->getAction() == GroupMembershipAction::ACTION_JOINED ||
                    $x->getAction() == GroupMembershipAction::ACTION_JOINED_APPLICATION_ACCEPTED);
        })
        ->count();
    }

    public function getRegion()
    {
        if ($this->allLocales) {
            return 'All Sites';
        } else {
            $regions = '';
            foreach ($this->sites as $site) {
                $regions .=  '['.$site->getName().']';
            }

            return $regions;
        }
    }


    public function setDeals($value)
    {
        $this->deals = $value;
    }

    public function getDeals()
    {
        return $this->deals;
    }

    public function setEvents($value)
    {
        $this->events = $value;
    }
    public function getEvents()
    {
        return $this->events;
    }
    public function getNumEvents() {
        $count = 0;
        foreach($this->events as $event)
            if($event->getDeleted() == 0)
                $count++;
        return $count;
    }
    public function addEvent($event)
    {
        $this->events[] = $event;
    }

    public function isOwner($user)
    {
        if (!$user) {
            return false;
        }

        return $this->owner === $user;
    }
    public function isMember($user)
    {
        if (!$user) {
            return false;
        }
        if (in_array($user, $this->members->toArray())){
            return true;
        }

        return false;
    }

    public function getReportThreshold()
    {
        return 3;
    }

    public function getSearchFacetType()
    {
        return 'group';
    }

    public function getSearchEntityType()
    {
        return 'group';
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
        return $this->createdAt;
    }

    public function getDeleteSearchDocument()
    {
        return $this->deleted;
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function getTaggableType()
    {
        return 'platformd_group';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }

    public function getEntrySetRegistration() {
        return $this->entrySetRegistration;
    }

    public function createEntrySetRegistration() {
        $this->entrySetRegistration = new EntrySetRegistry($this);
        return $this->entrySetRegistration;
    }

    public function getEntrySets() {
        return $this->entrySetRegistration->getEntrySets();
    }

    public function addSponsorRegistration($sponsorRegistration)
    {
        $this->sponsorRegistrations->add($sponsorRegistration);
    }
    public function getSponsorRegistrations()
    {
        return $this->sponsorRegistrations;
    }

    public function createSponsorRegistration()
    {
        $sponsorRegistration = new SponsorRegistry($this, null, null, null);
        $this->addSponsorRegistration($sponsorRegistration);

        return $sponsorRegistration;
    }

    public function getSponsors()
    {
        $sponsorRegistrations = $this->sponsorRegistrations->toArray();

        usort($sponsorRegistrations, function ($a, $b) {
            return ($a->getLevel() - $b->getLevel());
        });

        $sponsors = array();
        foreach ($sponsorRegistrations as $reg) {
            $sponsors[] = $reg->getSponsor();
        }
        return $sponsors;
    }

    public function addRegistrationField($registrationField)
    {
        $registrationField->setGroup($this);
        $this->registrationFields->add($registrationField);
    }

    public function setRegistrationFields($registrationFields)
    {
        $this->registrationFields = new ArrayCollection();
        foreach ($registrationFields as $field) {
            $this->addRegistrationField($field);
        }
    }

    public function getRegistrationFields()
    {
        return $this->registrationFields;
    }

    public function isMemberOf(User $user) {
        if($user->getId() == $this->getOwner()->getId())
            return true;

        foreach ($this->getMembers() as $member) {
            if($user->getId() == $member->getId())
                return true;
        }

        return false;
    }
    
    
    public function getParent() {
        return $this->parentGroup;    
    }
    
    public function setParent($parent) {
        $this->parentGroup = $parent;
    }
    
    public function addChild($child) {
        $this->childGroups->add($child);
    }

    public function getChildren() {
        return $this->childGroups;
    }
    public function addHtmlPage($htmlPage)
    {
        $this->htmlPages[] = $htmlPage;
    }
    public function removeHtmlPage($htmlPage)
    {
        $this->htmlPages->removeElement($htmlPage);
    }
    public function getHtmlPages()
    {
        return $this->htmlPages;
    }

    public function getHashTag() {
        return str_replace('-', '', $this->slug);
    }
}
