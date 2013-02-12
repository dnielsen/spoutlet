<?php

namespace Platformd\GroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Platformd\GroupBundle\Entity\GroupDiscussionPost
 *
 * @ORM\Table(name="pd_group_discussion_post")
 * @ORM\Entity(repositoryClass="Platformd\GroupBundle\Entity\GroupDiscussionPostRepository")
 */
class GroupDiscussionPost implements ReportableContentInterface
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
     * @var \Platformd\GroupBundle\Entity\GroupDiscussion
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\GroupDiscussion")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $groupDiscussion;

    /**
     * The person who created this group discussion
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $author;

    /**
     * @var string $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     * @Assert\NotNull
     */
    private $content;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updated;

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
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="groupDiscussionPost")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */
    private $contentReports;

    public function __construct()
    {
        $this->contentReports = new ArrayCollection();
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    function getContentType()
    {
        return "GroupDiscussionPost";
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
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
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
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     */
    public function setGroupDiscussion($groupDiscussion)
    {
        $this->groupDiscussion = $groupDiscussion;
    }

    /**
     * @return \Platformd\GroupBundle\Entity\GroupDiscussion
     */
    public function getGroupDiscussion()
    {
        return $this->groupDiscussion;
    }
}
