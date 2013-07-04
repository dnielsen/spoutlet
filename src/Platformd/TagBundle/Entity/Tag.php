<?php

namespace Platformd\TagBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Platformd\UserBundle\Entity\User;
use Platformd\TagBundle\Entity\Tagging;

/**
 * @ORM\Table(name="pd_tags")
 * @ORM\Entity(repositoryClass="Platformd\TagBundle\Repository\TagRepository")
 */
class Tag
{
    const STATUS_ACTIVE = 'Active';
    const STATUS_INAPPROPRIATE = 'Inappropriate';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotNull(message="tags.errors.empty_name")
     */
    protected $name;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var Platformd\TagBundle\Entity\Tagging
     *
     * @ORM\OneToMany(targetEntity="Platformd\TagBundle\Entity\Tagging", mappedBy="tag", fetch="EAGER")
     */
    protected $tagging;

    /**
     * @var string $status
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    protected $status;

    /**
     * @ORM\Column(name="times_used", type="integer")
     */
    protected $timesUsed = 0;

    /**
     * The person who created this tag
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $author;

    /**
     * Constructor
     *
     * @param string $name Tag's name
     */
    public function __construct($name=null,$author=null)
    {
        $this->setName($name);
        $this->setCreatedAt(new \DateTime('now'));
        $this->setUpdatedAt(new \DateTime('now'));
        $this->setStatus(Tag::STATUS_ACTIVE);
        $this->setAuthor($author);
    }

    /**
     * Returns tag's id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the tag's name
     *
     * @param string $name Name to set
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns tag's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set createdAt
     *
     * @param DateTime $value
     */
    public function setCreatedAt(\DateTime $value)
    {
        $this->createdAt = $value;
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
     * Set updatedAt
     *
     * @param DateTime $value
     */
    public function setUpdatedAt(\DateTime $value)
    {
        $this->updatedAt = $value;
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
     * Set status
     *
     * @param string $value
     */
    public function setStatus($value)
    {
        $this->status = $value;
    }

    /**
     * Set tagging
     *
     * @param Platformd\TagBundle\Entity\Tagging
     */
    public function setTagging($value)
    {
        $this->tagging = $value;
    }

    /**
     * Get tagging
     *
     * @return Platformd\TagBundle\Entity\Tagging
     */
    public function getTagging()
    {
        return $this->tagging;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set timesUsed
     *
     * @param int $value
     */
    public function setTimesUsed($value)
    {
        $this->timesUsed = $value;
    }

    /**
     * Get timesUsed
     *
     * @return int
     */
    public function getTimesUsed()
    {
        return $this->timesUsed;
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
     * Get author
     *
     * @return Platformd\UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }
}
