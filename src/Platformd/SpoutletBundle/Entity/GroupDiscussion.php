<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Platformd\SpoutletBundle\Entity\GroupDiscussion
 *
 * @ORM\Table(name="pd_group_discussion")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupDiscussionRepository")
 */
class GroupDiscussion implements LinkableInterface, ReportableContentInterface
{
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
     * The person who created this group discussion
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $author;

    /**
     * @var \DateTime $createdAt
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $deleted = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $deletedReason = null;

    /**
     * @var string $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     * @Assert\NotNull
     */
    private $content;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="groupDiscussion")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */
    private $contentReports;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $replyCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $viewCount = 0;

    /**
     * Last person to have updated the thread
     *
     * @var \Platformd\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $lastUpdatedBy;

    /**
     * Last post id
     *
     * @var int
     *
     * @ORM\Column(name="last_post_id", type="integer", nullable=true)
     */
    private $lastPostId = 0;

    public function __construct()
    {
        $this->contentReports = new ArrayCollection();
    }

    /**
     * If there is a set URL that should be used without doing anything else, return it here
     *
     * @return string
     */
    function getLinkableOverrideUrl()
    {
        return false;
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    function getLinkableRouteName()
    {
        return 'group_view_discussion';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    function getLinkableRouteParameters()
    {
        return array(
            'id' => $this->getGroup()->getId(),
            'discussionId' => $this->getId()
        );
    }

    function getContentType()
    {
        return "GroupDiscussion";
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
     * @param \Platformd\UserBundle\Entity\User $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
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
     * @param \Doctrine\Common\Collections\ArrayCollection $contentReports
     */
    public function setContentReports($contentReports)
    {
        $this->contentReports = $contentReports;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getContentReports()
    {
        return $this->contentReports;
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

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeletedReason($deletedReason)
    {
        $this->deletedReason = $deletedReason;
    }

    public function getDeletedReason()
    {
        return $this->deletedReason;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Group $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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

    /**
     * @param int $replyCount
     */
    public function setReplyCount($replyCount)
    {
        $this->replyCount = $replyCount;
    }

    /**
     * @return int
     */
    public function getReplyCount()
    {
        return $this->replyCount;
    }

    /**
     * @param int $inc
     */
    public function incReplyCount($inc)
    {
        $this->replyCount += $inc;
    }

    /**
     * @param int $viewCount
     */
    public function setViewCount($viewCount)
    {
        $this->viewCount = $viewCount;
    }

    /**
     * @return int
     */
    public function getViewCount()
    {
        return $this->viewCount;
    }

    /**
     * @param int $inc
     */
    public function incViewCount($inc)
    {
        $this->viewCount += $inc;
    }

    public function getLastUpdatedBy()
    {
        return $this->lastUpdatedBy;
    }

    public function setLastUpdatedBy($value)
    {
        $this->lastUpdatedBy = $value;
    }

    public function getLastPostId()
    {
        return $this->lastPostId;
    }

    public function setLastPostId($value)
    {
        $this->lastPostId = $value;
    }
}
