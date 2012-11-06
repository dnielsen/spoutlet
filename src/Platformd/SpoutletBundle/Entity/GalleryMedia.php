<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\MediaBundle\Entity\GalleryMedia
 *
 * @ORM\Table(name="pd_gallery_media")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GalleryMediaRepository")
 */
class GalleryMedia implements LinkableInterface, ReportableContentInterface
{

    const IMAGE = 'image';
    const VIDEO = 'video';

    static private $validCategories = array(
        self::IMAGE,
        self::VIDEO,
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
     * @var string $category
     * @ORM\Column(name="category", type="string", length=50)
     */
    private $category;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotNull
     */
    private $title;

    /**
     * @var string $description
     *
     * @ORM\Column(name="description", type="string", length=512, nullable=true)
     */
    private $description;

    /**
     * The person who created this gallery image
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
     * @ORM\Column(type="boolean")
     */
    private $published = false;

    /**
     * @ORM\Column(name="featured", type="boolean", nullable=true)
     */
    protected $featured;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $image;

    /**
     * @var string $youtubeId
     *
     * @ORM\Column(name="youtubeId", type="string", length=255, nullable=true)
     */
    private $youtubeId;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Gallery")
     * @ORM\JoinTable(name="pd_gallery_media_galleries")
     */
    private $galleries;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\ContentReport", mappedBy="galleryMedia")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\OrderBy({"reportedAt" = "DESC"})
     */

    protected $contentReports;

    /*
     * @ORM\Column(name="views", type="integer")
     */
    private $views = 0;

    public function __construct()
    {
        $this->galleries = new ArrayCollection();
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

    public function setCategory($category)
    {
        if (!in_array($category, self::$validCategories)) {
            throw new \InvalidArgumentException(sprintf('Invalid category "%s" given', $category));
        }

        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
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

    public function getPublished()
    {
        return $this->getPublished();
    }

    public function setPublished($published)
    {
        $this->published = $published;
    }

    public function getFeatured()
    {
        return $this->featured;
    }

    public function setFeatured($featured)
    {
        $this->featured = $featured;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getYoutubeId()
    {
        return $this->youtubeId;
    }

    public function setYoutubeId($youtubeId)
    {
        $this->youtubeId = $youtubeId;
    }

    public function getGalleries()
    {
        return $this->galleries;
    }

    public function setGalleries($galleries)
    {
        $this->galleries = $galleries;
    }


    public function getContentType() {
        return "GalleryMedia";
    }

    public function getContentReports()
    {
        return $this->contentReports;
    }

    public function setContentReports($contentReports)
    {
        $this->contentReports = $contentReports;
    }

    public function getViews()
    {
        return $this->views;
    }

    public function setViews($views)
    {
        $this->views = $views;

    }

    public static function getValidCategories()
    {
        return self::$validCategories;
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
        return $this->getCategory() == 'image' ? 'gallery_view_image' : 'gallery_view_video';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function  getLinkableRouteParameters()
    {
        return array(
            'id' => $this->getId(),
        );
    }

     /**
     * Used to return the commenting thread id that should be used for this gallery image
     */
    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A gallery media item needs an id before it can have a comment thread');
        }

        $commentPrefix = $this->getCategory() == 'image' ? 'gallery_image-' : 'gallery_video-';

        return $commentPrefix.$this->getId();
    }
}
