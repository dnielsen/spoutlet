<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Entity\Site;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Util\Urlizer;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\ExecutionContext;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Our gallery entity
 *
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GalleryRepository")
 * @ORM\Table(name="pd_gallery")
 * @UniqueEntity(fields={"name"}, message="This gallery name is already used.")
 * @UniqueEntity(fields={"slug"}, message="This URL is already used.  If you have left slug blank, this means that an existing gallery is already using this gallery name.")
 * @Assert\Callback(methods={"validateSlug"})
 */
class Gallery implements LinkableInterface
{
    const COMMENT_PREFIX = 'gallery-';
    const DELETED_BY_OWNER = 'by_owner';
    const DELETED_BY_ADMIN = 'by_admin';

    static private $validDeletedReasons = array(
        self::DELETED_BY_OWNER,
        self::DELETED_BY_ADMIN,
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
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string $name
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $name;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_gallery_site")
     */
    private $sites;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\GalleryCategory")
     * @ORM\JoinTable(name="pd_gallery_gallery_category")
     */
    private $categories;

    /**
     * The person who created this gallery
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", cascade={"delete"})
     * @ORM\JoinColumn(onDelete="cascade")
     */
    protected $owner;

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
     * @var boolean $deleted
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted = false;

    /**
     * @ORM\Column(name="deleted_reason", type="string", length=50, nullable=true)
     */
    private $deletedReason;

    /**
     * @ORM\Column(name="sites_positions", type="array", nullable=true)
     */
    private $sitesPositions;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\GalleryTranslation", mappedBy="gallery", cascade={"all"})
     */
    private $translations;

    public function __construct()
    {
        $this->sites            = new ArrayCollection();
        $this->categories       = new ArrayCollection();
        $this->sitesPositions   = array();
        $this->translations     = new ArrayCollection();
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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        # this allows slug to be left blank and set elsewhere without it getting overridden here
        if (!$slug) {
            return;
        }

        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->setSlug(Urlizer::urlize($name));
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName($siteId = null)
    {
        if (null === $siteId) {
            return $this->name;
        }

        foreach ($this->getTranslations() as $translation) {
            if ($translation->getSite()->getId() == $siteId) {
                return $translation->getName() ?: $this->name;
            }
        }

        return$this->name;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $sites
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSites()
    {
        return $this->sites;
    }

    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    public function getCategories()
    {
        return $this->categories;
    }


    /**
     * @param \Platformd\UserBundle\Entity\User $owner
     */
    public function setOwner(UserInterface $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
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

    /**
     * @param \DateTime $createdAt
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
     * Set deleted
     *
     * @param boolean $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeletedReason($value)
    {

        if (!in_array($value, self::$validDeletedReasons)) {
            throw new \InvalidArgumentException(sprintf('Invalid reason for deletion "%s" given', $value));
        }

        $this->deletedReason = $value;
    }

    public function getDeletedReason()
    {
        return $this->deletedReason;
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
        return 'group_show';
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
            'slug' => $this->getSlug(),
        );
    }

    public function __toString()
    {
        return $this->name;
    }

    public function isVisibleOnSite($site)
    {
        return $this->sites->contains($site);
    }

    public function getSitesPositions()
    {
        return $this->sitesPositions;
    }

    public function setSitesPositions($sitesPositions)
    {
        $this->sitesPositions = $sitesPositions;
    }

    public function validateSlug(ExecutionContext $executionContext)
    {
        if ($this->getSlug()) {
            return;
        }

        $oldPath = $executionContext->getPropertyPath();
        $executionContext->setPropertyPath($oldPath.'.slug');

        $executionContext->addViolation(
            'Automatic generation of the URL string failed. Please enter this manually.',
            array(),
            null
        );

        $executionContext->setPropertyPath($oldPath);
    }

    public function getTranslations()
    {
        return $this->translations ?: new ArrayCollection();
    }

    public function setTranslations($value)
    {
        $this->translations = $value;
    }
}

