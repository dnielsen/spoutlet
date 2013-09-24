<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\SpoutletBundle\Entity\Thread
 *
 * @ORM\Table(name="commenting_thread")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\ThreadRepository")
 */
class Thread
{
	/**
     * @ORM\Id
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $id;

    /**
     * @ORM\Column(name="is_commentable", type="boolean")
     */
    protected $isCommentable = true;

    /**
     * @ORM\Column(name="last_commented_at", type="datetime", nullable=true)
     */
    protected $lastCommentAt = null;

    /**
     * @ORM\Column(name="comment_count", type="integer")
     */
    protected $commentCount = 0;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $permalink;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\Comment", mappedBy="thread")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getPermalink()
    {
        return $this->permalink;
    }

    public function setPermalink($permalink)
    {
        $this->permalink = $permalink;
    }

    public function isCommentable()
    {
        return $this->isCommentable;
    }

    public function setIsCommentable($isCommentable)
    {
        $this->isCommentable = (bool) $isCommentable;
    }

    public function getCommentCount()
    {
        return $this->commentCount;
    }

    public function setCommentCount($commentCount)
    {
        $this->commentCount = intval($commentCount);
    }

    public function incrementCommentCount($by = 1)
    {
        return $this->commentCount += intval($by);
    }

    public function getLastCommentAt()
    {
        return $this->lastCommentAt;
    }

    public function setLastCommentAt($lastCommentAt)
    {
        $this->lastCommentAt = $lastCommentAt;
    }

    public function getComments()
    {
        return $this->comments;
    }
}
