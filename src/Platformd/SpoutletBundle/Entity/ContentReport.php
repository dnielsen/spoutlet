<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * Platformd\SpoutletBundle\Entity\ContentReport
 *
 * @ORM\Table(name="pd_content_report")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\ContentReportRepository")
 */
class ContentReport
{

    const DELETED_BY_REPORT         = 'REPORTED_PENDING_INVESTIGATION';
    const DELETED_BY_REPORT_ADMIN   = 'REPORTED_AND_REMOVED_BY_ADMIN';

    static private $validReasons = array(
        'inappropriate_content',
        'spam',
        'violates_intellectual_property',
        'individual_harrassing_me',
    );

    static private $typeTranslationMap = array(
        'Group' => 'content_reporting.report_type_group',
        'GroupVideo' => 'content_reporting.report_type_group_video',
        'GroupNews' => 'content_reporting.report_type_group_news',
        'GroupImage' => 'content_reporting.report_type_group_image',
        'GroupDiscussion' => 'content_reporting.report_type_group_discussion',
        'GroupDiscussionPost' => 'content_reporting.report_type_group_discussion_post',
        'Image' => 'content_reporting.report_type_image',
        'Video' => 'content_reporting.report_type_video',
        'Comment' => 'content_reporting.report_type_comment',
        'Unknown' => 'content_reporting.report_type_unknown',
        'GroupEvent' => 'content_reporting.report_type_group_event',
    );

    static private $typeClassMap = array(
        'GroupEvent' => 'EventBundle:GroupEvent',
        'GroupImage' => 'SpoutletBundle:GroupImage',
        'GroupNews' => 'SpoutletBundle:GroupNews',
        'GroupVideo' => 'SpoutletBundle:GroupVideo',
        'Group' => 'SpoutletBundle:Group',
        'Comment' => 'SpoutletBundle:Comment',
        'GalleryMedia' => 'SpoutletBundle:GalleryMedia',
        'GroupDiscussion' => 'SpoutletBundle:GroupDiscussion',
        'GroupDiscussionPost' => 'SpoutletBundle:GroupDiscussionPost',
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
     * @var string $reason
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $reason;

    /**
     * The person who created this report
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $reporter;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(name="reported_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $reportedAt;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinColumn(nullable=false)
     */
    private $site;

    /**
     * @ORM\Column(type="boolean")
     */

    private $deleted = false;

    /**
     * @ORM\ManyToOne(targetEntity="GroupImage")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupImage = null;

    /**
     * @ORM\ManyToOne(targetEntity="GroupNews")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupNews = null;

    /**
     * @ORM\ManyToOne(targetEntity="GroupVideo")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupVideo = null;

    /**
     * @ORM\ManyToOne(targetEntity="GalleryMedia")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $galleryMedia = null;

    /**
     * @ORM\ManyToOne(targetEntity="Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $group = null;

    /**
     * @ORM\ManyToOne(targetEntity="GroupDiscussion")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupDiscussion = null;

    /**
     * @ORM\ManyToOne(targetEntity="GroupDiscussionPost")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupDiscussionPost = null;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Comment")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $comment = null;


    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupEvent = null;

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
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getReporter()
    {
        return $this->reporter;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $reporter
     */
    public function setReporter(User $reporter)
    {
        $this->reporter = $reporter;
    }

    /**
     * @return \DateTime
     */
    public function getReportedAt()
    {
        return $this->reportedAt;
    }

    /**
     * @param \DateTime $reportedAt
     */
    public function setReportedAt($reportedAt)
    {
        $this->reportedAt = $reportedAt;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getGroupImage()
    {
        return $this->groupImage;
    }

    public function setGroupImage($value)
    {
        $this->groupImage = $value;
    }

    public function getGroupNews()
    {
        return $this->groupNews;
    }

    public function setGroupNews($value)
    {
        $this->groupNews = $value;
    }

    public function getGroupVideo()
    {
        return $this->groupVideo;
    }

    public function setGroupVideo($value)
    {
        $this->groupVideo = $value;
    }

    public function getGalleryMedia()
    {
        return $this->galleryMedia;
    }

    public function setGalleryMedia($value)
    {
        $this->galleryMedia = $value;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($value)
    {
        $this->group = $value;
    }


    public function getGroupDiscussion()
    {
        return $this->groupDiscussion;
    }

    public function setGroupDiscussion($groupDiscussion)
    {
        $this->groupDiscussion = $groupDiscussion;
    }

    public function setGroupDiscussionPost($groupDiscussionPost)
    {
        $this->groupDiscussionPost = $groupDiscussionPost;
    }

    public function getGroupDiscussionPost()
    {
        return $this->groupDiscussionPost;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($value)
    {
        $this->comment = $value;
    }

    public function getGroupEvent()
    {
        return $this->groupEvent;
    }

    public function setGroupEvent($value)
    {
        $this->groupEvent = $value;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setSite($value)
    {
        $this->site = $value;
    }

    public function getValidReasons()
    {
        return self::$validReasons;
    }

    public function setReason($reason)
    {
        if (!in_array($reason, self::$validReasons)) {
            throw new \InvalidArgumentException(sprintf('Invalid report reason "%s" given', $reason));
        }

        $this->reason = $reason;
    }

    public function getReason()
    {
        return $this->reason;
    }

    static public function getTypeTranslationKey($type)
    {
        if (array_key_exists($type, self::$typeTranslationMap)) {
            return self::$typeTranslationMap[$type];
        }

        return self::$typeTranslationMap['Unknown'];
    }

    static public function getTypeClass($type)
    {
        if (array_key_exists($type, self::$typeClassMap)) {
            return self::$typeClassMap[$type];
        }

        return false;
    }
}
