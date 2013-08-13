<?php

namespace Platformd\VideoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Platformd\UserBundle\Entity\User,
    Platformd\SpoutletBundle\Link\LinkableInterface,
    Platformd\SpoutletBundle\Model\CommentableInterface,
    Platformd\SpoutletBundle\Model\ReportableContentInterface,
    Platformd\VideoBundle\Validator\YoutubeRestriction as AssertYoutubeRestrictions,
    Platformd\VideoBundle\Validator\YoutubeGroupCategory as AssertYoutubeGroupCategory,
    Platformd\SearchBundle\Model\IndexableInterface,
    Platformd\TagBundle\Model\TaggableInterface
;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Sluggable\Util\Urlizer
;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection
;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use DateTime;

/**
 * @ORM\Table(name="pd_videos_youtube")
 * @ORM\Entity(repositoryClass="Platformd\VideoBundle\Repository\YoutubeVideoRepository")
 * @UniqueEntity(fields={"title"}, message="youtube.errors.unique_title_slug")
 * @AssertYoutubeGroupCategory()
 */
class YoutubeVideo implements LinkableInterface, CommentableInterface, ReportableContentInterface, IndexableInterface, TaggableInterface
{
    const DELETED_REASON_BY_AUTHOR = 'DELETED_BY_AUTHOR';
    const DELETED_REASON_BY_ADMIN  = 'REPORTED_AND_REMOVED_BY_ADMIN';

    const YOUTUBE_HQ_THUMBNAIL_URL = '//i.ytimg.com/vi/%s/hqdefault.jpg';
    const YOUTUBE_SQ_THUMBNAIL_URL = '//i.ytimg.com/vi/%s/mqdefault.jpg';

    const SEARCH_PREFIX  = 'video_';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $slug
     *
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    protected $slug;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255, unique=true)
     * @Assert\NotNull(message="youtube.errors.no_title")
     */
    protected $title;

    /**
     * @var string $description
     *
     * @ORM\Column(name="description", type="string", length=512)
     * @Assert\NotNull(message="youtube.errors.no_description")
     */
    protected $description;

    /**
     * The person who created this video
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $author;

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
     * @var \DateTime $lastViewedAt
     *
     * @ORM\Column(name="last_viewed_at", type="datetime", nullable=true)
     */
    protected $lastViewedAt;

    /**
     * @var boolean $featured
     *
     * @ORM\Column(type="boolean")
     */
    protected $featured = false;

    /**
     * @var \DateTime $featuredAt
     * @ORM\Column(name="featured_at", type="datetime", nullable=true)
     */
    protected $featuredAt;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $deleted = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $deletedReason = null;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Gallery")
     * @ORM\JoinTable(name="pd_videos_youtube_galleries")
     */
    private $galleries;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\GroupBundle\Entity\Group", inversedBy="videos")
     * @ORM\JoinTable(name="pd_videos_youtube_groups")
     */
    private $groups;

    /**
     * @ORM\Column(name="youtube_id", type="string")
     * @AssertYoutubeRestrictions
     */
    private $youtubeId;

    /**
     * @ORM\Column(name="youtube_link", type="string")
     * @Assert\NotNull(message="youtube.errors.no_link")
     *
     */
    private $youtubeLink;

    /**
     * @ORM\Column(type="integer")
     */
    private $views = 0;


    /**
     * @ORM\Column(type="integer")
     *
     */
    private $duration = 0;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\VideoBundle\Entity\YoutubeVote", mappedBy="video")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $votes;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id")
     */
    protected $site;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="youtubeVideo")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */
    protected $contentReports;

    /**
     * 1 = allow, 0 = deny
     * @ORM\Column(name="restriction_type", type="boolean", nullable="true")
     */
    private $restrictionType;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Country")
     * @ORM\JoinTable(name="pd_videos_youtube_restriction_countries")
     */
    private $restrictionCountries;

    /**
     * @ORM\Column(name="restrictions_checked", type="datetime", nullable=true)
     */
    private $restrictionsChecked;

    /**
     * @ORM\Column(name="is_accessible", type="boolean", nullable="true")
     */
    private $isAccessible = true;

    /**
     * @var Platformd\TagBundle\Entity\Tag[]
     *
     */
    private $tags;

    public function __construct()
    {
        $this->votes                = new ArrayCollection();
        $this->contentReports       = new ArrayCollection();
        $this->restrictionCountries = new ArrayCollection();
        $this->groups               = array();
        $this->galleries            = array();
    }

    /**
     * Get Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * Set slug
     *
     * @param string $value
     */
    public function setSlug($value)
    {
        $this->slug = $value;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $value
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $value
     */
    public function setDescription($value)
    {
        $this->description = $value;
    }

    /**
     * Get author
     *
     * @return Platformd\UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set author
     *
     * @param Platformd\UserBundle\Entity\User $value
     */
    public function setAuthor($value)
    {
        $this->author = $value;
    }

    /**
     * Get createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param DateTime $value
     */
    public function setCreatedAt($value)
    {
        $this->createdAt = $value;
    }

    /**
     * Get updatedAt
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedAt
     *
     * @param DateTime $value
     */
    public function setUpdatedAt($value)
    {
        $this->updatedAt = $value;
    }

    /**
     * Get lastViewedAt
     *
     * @return DateTime
     */
    public function getLastViewedAt()
    {
        return $this->lastViewedAt;
    }

    /**
     * Set lastViewedAt
     *
     * @param DateTime $value
     */
    public function setLastViewedAt($value)
    {
        $this->lastViewedAt = $value;
    }

    /**
     * Get featured
     *
     * @return boolean
     */
    public function getFeatured()
    {
        return $this->featured;
    }

    /**
     * Set featured
     *
     * @param boolean $value
     */
    public function setFeatured($value)
    {
        if($value) {
            $this->featuredAt = new DateTime('now');
        }

        $this->featured = $value;
    }

    /**
     * Get featuredAt
     *
     * @return DateTime
     */
    public function getFeaturedAt()
    {
        return $this->featuredAt;
    }

    /**
     * Set featuredAt
     *
     * @param DateTime $value
     */
    public function setFeaturedAt($value)
    {
        $this->featuredAt = $value;
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
     * Set deleted
     *
     * @param boolean $value
     */
    public function setDeleted($value)
    {
        $this->deleted = $value;
    }

    /**
     * Get deletedReason
     *
     * @return string
     */
    public function getDeletedReason()
    {
        return $this->deletedReason;
    }

    /**
     * Set deletedReason
     *
     * @param string $value
     */
    public function setDeletedReason($value)
    {
        $this->deletedReason = $value;
    }

    /**
     * Get galleries
     *
     * @return Platformd\SpoutletBundle\Entity\Gallery
     */
    public function getGalleries()
    {
        return $this->galleries;
    }

    /**
     * Set galleries
     *
     * @param Platformd\SpoutletBundle\Entity\Gallery $value
     */
    public function setGalleries($value)
    {
        $this->galleries = $value;
    }

    /**
     * Get groups
     *
     * @return Platformd\GroupBundle\Entity\Group
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set groups
     *
     * @param Platformd\GroupBundle\Entity\Group $value
     */
    public function setGroups($value)
    {
        $this->groups = $value;
    }

    /**
     * Get thumbnailHq
     *
     * @return string
     */
    public function getThumbnailHq()
    {
        return sprintf(self::YOUTUBE_HQ_THUMBNAIL_URL, $this->youtubeId);
    }

    /**
     * Get thumbnailSq
     *
     * @return string
     */
    public function getThumbnailSq()
    {
        return sprintf(self::YOUTUBE_SQ_THUMBNAIL_URL, $this->youtubeId);
    }

    /**
     * Get youtubeId
     *
     * @return string
     */
    public function getYoutubeId()
    {
        return $this->youtubeId;
    }

    /**
     * Set youtubeId
     *
     * @param string $value
     */
    public function setYoutubeId($value)
    {
        $this->youtubeId = $value;
    }

    /**
     * Get youtubeLink
     *
     * @return string
     */
    public function getYoutubeLink()
    {
        return $this->youtubeLink;
    }

    /**
     * Set youtubeLink
     *
     * @param string $value
     */
    public function setYoutubeLink($value)
    {
        $this->youtubeLink = $value;
    }

    /**
     * Get views
     *
     * @return integer
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * Set views
     *
     * @param integer $value
     */
    public function setViews($value)
    {
        $this->views = $value;
    }

    public function addView()
    {
        $this->lastViewedAt = new DateTime('now');
        $this->views += 1;
    }

    public function subtractView()
    {
        $this->views -= 1;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set duration
     *
     * @param integer $value
     */
    public function setDuration($value)
    {
        $this->duration = $value;
    }

    public function getFormattedDuration()
    {
        $time = round($this->duration);

        if($time >= 3600) {
            return sprintf('%02d:%02d:%02d', ($time/3600),($time/60%60), $time%60);
        }

        return sprintf('%02d:%02d', ($time/60%60), $time%60);
    }

    /**
     * Get votes
     *
     * @return integer
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * Set votes
     *
     * @param Platformd\VideoBundle\Entity\YoutubeVote $value
     */
    public function setVotes($value)
    {
        $this->votes = $value;
    }

    public function addVote($vote)
    {
        $this->votes->add($vote);
    }

    /**
     * Get site
     *
     * @return Platformd\SpoutletBundle\Entity\Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set site
     *
     * @param Platformd\SpoutletBundle\Entity\Site $value
     */
    public function setSite($value)
    {
        $this->site = $value;
    }

    public function getContentReports()
    {
        return $this->contentReports;
    }

    public function setContentReports($value)
    {
        $this->contentReports = $value;
    }

    public function getRestrictionType()
    {
        return $this->restrictionType;
    }

    public function setRestrictionType($value)
    {
        $this->restrictionType = $value;
    }

    public function getRestrictionCountries()
    {
        return $this->restrictionCountries;
    }

    public function setRestrictionCountries($value)
    {
        $this->restrictionCountries = $value;
    }

    public function getRestrictionsChecked()
    {
        return $this->restrictionsChecked;
    }

    public function setRestrictionsChecked($value)
    {
        $this->restrictionsChecked = $value;
    }

    public function getIsAccessible()
    {
        return $this->isAccessible;
    }

    public function setIsAccessible($value)
    {
        $this->isAccessible = $value;
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
        //Will need updated when vdeo galleries are implemented.
        return 'youtube_view';
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
     * Used to return the commenting thread id that should be used for this youtube video
     */
    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A gallery Youtube Video needs an id before it can have a comment thread');
        }

        return 'youtube-'.$this->getId();
    }

    public function getContentType()
    {
        return "YoutubeVideo";
    }

    public function getReportThreshold()
    {
        return 3;
    }

    public function getSearchEntityType()
    {
        return 'video';
    }

    public function getSearchFacetType()
    {
        return 'video';
    }

    public function getSearchId()
    {
        return self::SEARCH_PREFIX.$this->id;
    }

    public function getSearchTitle()
    {
        return $this->title;
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
        $isVisible = false;

        if ($this->galleries->count() > 0) {
            $isVisible = true;
        }

        if (!$isVisible) {
            foreach ($this->groups as $group) {
                if ($group->getIsPublic() && !$group->getDeleted()) {
                    $isVisible = true;
                    break;
                }
            }
        }

        return $this->deleted || false == $this->isAccessible || false === $isVisible;
    }

    public function getSites()
    {
        $sites = array();

        foreach ($this->galleries as $gallery) {
            foreach ($gallery->getSites() as $site) {
                if (!isset($sites[$site->getId()])) {
                    $sites[$site->getId()] = $site;
                }
            }
        }

        foreach ($this->groups as $group) {
            if ($group->getIsPublic() && !$group->getDeleted()) {
                foreach ($group->getSites() as $site) {
                    if (!isset($sites[$site->getId()])) {
                        $sites[$site->getId()] = $site;
                    }
                }
            }
        }

        return $sites;
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function getTaggableType()
    {
        return 'platformd_youtube_video';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }
}
