<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\GroupNews
 *
 * @ORM\Table(name="pd_group_news")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupNewsRepository")
 */
class GroupNews
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
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Group", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
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
     * @ORM\Column(name="article", type="text")
     * @Assert\NotNull
     */
    private $article;

    /**
     * The person who created this group news article
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", cascade={"delete"})
     * @ORM\JoinColumn(onDelete="cascade")
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
     * Set article
     *
     * @param string $article
     */
    public function setArticle($article)
    {
        $this->article = $article;
    }

    /**
     * Get article
     *
     * @return string
     */
    public function getArticle()
    {
        return $this->article;
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
}
