<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Validator as CustomAssert;

/**
 * Platformd\SpoutletBundle\Entity\GroupVideo
 *
 * @ORM\Table(name="pd_group_video")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupVideoRepository")
 */
class GroupVideo implements LinkableInterface, ReportableContentInterface
{
    const COMMENT_PREFIX = 'group_video-';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     *
     * @var \Platformd\SpoutletBundle\Entity\Group
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $group;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotNull
     */
    private $title;

    /**
     * @var string $article
     *
     * @ORM\Column(name="you_tube_video_id", type="string")
     * @Assert\NotNull
     * @CustomAssert\YouTube(message = "The YouTube ID you entered is invalid.")
     */
    private $youTubeVideoId;

    /**
     * @var string $youTubeThumb
     *
     * @ORM\Column(name="you_tube_thumb", type="string")
     */
    private $youTubeThumb;

    /**
     * The person who uploaded this group video
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
     * @ORM\Column(type="boolean")
     */

    private $deleted = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $deletedReason = null;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="groupVideo")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */

    protected $contentReports;

    public function __construct()
    {
        $this->contentReports = new ArrayCollection();
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
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     * Set youTubeVideoId
     *
     * @param string $youTubeVideoId
     */
    public function setYouTubeVideoId($youTubeVideoId)
    {
        $this->youTubeVideoId = $youTubeVideoId;
    }

    /**
     * Get youTubeVideoId
     *
     * @return string
     */
    public function getYouTubeVideoId()
    {
        return $this->youTubeVideoId;
    }

    /**
     * Set youTubeThumb
     *
     * @param string $youTubeThumb
     */
    public function setYouTubeThumb($youTubeThumb)
    {
        $this->youTubeThumb = $youTubeThumb;
    }

    /**
     * Get youTubeThumb
     *
     * @return string
     */
    public function getYouTubeThumb()
    {
        return $this->youTubeThumb;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $author
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
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

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($group)
    {
        $this->group = $group;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeletedReason()
    {
        return $this->deletedReason;
    }

    public function setDeletedReason($deletedReason)
    {
        $this->deletedReason = $deletedReason;
    }

    public function getContentType() {
        return "GroupVideo";
    }

    public function getContentReports()
    {
        return $this->contentReports;
    }

    public function setContentReports($contentReports)
    {
        $this->contentReports = $contentReports;
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
        return 'group_view_video';
    }

    public function getClass()
    {
        return get_class($this);
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function  getLinkableRouteParameters()
    {
        return array(
            'id' => $this->getGroup()->getId(),
            'videoId' => $this->getId()
        );
    }

     /**
     * Used to return the commenting thread id that should be used for this group video
     */
    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A group needs an id before it can have a comment thread');
        }

        return self::COMMENT_PREFIX.$this->getId();
    }
}
