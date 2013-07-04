<?php

namespace Platformd\TagBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\TagBundle\Model\TaggableInterface;

/**
 * @ORM\Table(name="pd_tagging")
 * @ORM\Entity
 */
class Tagging
{

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Platformd\TagBundle\Entity\Tag
     *
     * @ORM\ManyToOne(targetEntity="Platformd\TagBundle\Entity\Tag")
     */
    protected $tag;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_type", type="string", length="50")
     */
    protected $resourceType;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_id", type="string", length="50")
     */
    protected $resourceId;

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
     * Constructor
     */
    public function __construct(Tag $tag = null, TaggableInterface $resource = null)
    {
        if ($tag != null) {
            $this->setTag($tag);
        }

        if ($resource != null) {
            $this->setResource($resource);
        }

        $this->setCreatedAt(new \DateTime('now'));
        $this->setUpdatedAt(new \DateTime('now'));
    }

    /**
     * Returns tagging id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the tag object
     *
     * @param Tag $tag Tag to set
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * Returns the tag object
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Sets the resource
     *
     * @param Taggable $resource Resource to set
     */
    public function setResource(TaggableInterface $resource)
    {
        $this->resourceType = $resource->getTaggableType();
        $this->resourceId = $resource->getTaggableId();
    }

    /**
     * Returns the tagged resource type
     *
     * @return Taggable
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Returns the tagged resource id
     *
     * @return Taggable
     */
    public function getResourceId()
    {
        return $this->resourceId;
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
}
