<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\SpoutletBundle\Entity\Comment
 *
 * @ORM\Table(name="commenting_comment")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\CommentRepository")
 */
class Comment implements ReportableContentInterface
{
    const DELETED_BY_OWNER          = 'BY_OWNER';
    const DELETED_BY_ADMIN          = 'BY_ADMIN';
    const DELETED_BY_REPORT         = 'REPORTED_PENDING_INVESTIGATION';
    const DELETED_BY_REPORT_ADMIN   = 'REPORTED_AND_REMOVED_BY_ADMIN';
    const DELETED_BY_ADMIN_USER_BAN = 'BY_ADMIN_USER_BAN';

    static private $validDeletedReasons = array(
        self::DELETED_BY_OWNER,
        self::DELETED_BY_ADMIN,
        self::DELETED_BY_REPORT,
        self::DELETED_BY_REPORT_ADMIN,
        self::DELETED_BY_ADMIN_USER_BAN,
    );

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Thread")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Comment")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $parent;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $author;

    /**
     * @ORM\Column(name="body", type="text")
     */
    protected $body;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    //protected $votes;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\Comment", mappedBy="parent")
     */
    protected $replies;

    /**
     * @ORM\Column(name="deleted", type="boolean")
     */

    private $deleted = false;

    /**
     * @ORM\Column(name="deletedReason", type="string", length=50, nullable=true)
     */
    private $deletedReason;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="comment")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */
    protected $contentReports;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\CommentVote", mappedBy="comment")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $votes;

    public function __construct()
    {
        $this->replies          = new ArrayCollection();
        $this->contentReports   = new ArrayCollection();
        $this->votes            = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getThread()
    {
        return $this->thread;
    }

    public function setThread(Thread $thread)
    {
        $this->thread = $thread;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Comment $parent)
    {
        $this->parent = $parent;

        if (!$parent->getId()) {
            throw new InvalidArgumentException('Parent comment must be persisted.');
        }

        $parentReplies = $parent->getReplies()->add($this);
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getVotes()
    {
        return $this->votes;
    }

    public function getUpVoteCount()
    {
        $upvotes = $this->votes->filter(function ($x) { return $x->getVoteType() == 'up';});
        return count($upvotes);
    }

    public function getDownVoteCount()
    {
        $downvotes = $this->votes->filter(function ($x) { return $x->getVoteType() == 'down';});
        return count($downvotes);
    }

    public function getPublishedReplyCount()
    {
        $replies = $this->replies->filter(function ($x) { return !$x->getDeleted(); });
        return count($replies);
    }

    public function getReplies()
    {
        return $this->replies;
    }

    public function setReplies($replies)
    {
        $this->replies = $replies;
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

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function getContentReports()
    {
        return $this->contentReports;
    }

    public function setContentReports($value)
    {
        $this->contentReports = $value;
    }

    public function getContentType()
    {
        return "Comment";
    }

    public function getReportThreshold()
    {
        return 1;
    }

    public function getClass()
    {
        return get_class($this);
    }
}
