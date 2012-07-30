<?php

namespace Platformd\SpoutletBundle\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\MediaBundle\Entity\Media;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\UserBundle\Entity\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * Platformd\SpoutletBundle\Entity\Group
 *
 * @ORM\Table(name="pd_groups")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupRepository")
 */
class Group implements LinkableInterface, LocalesRelationshipInterface
{

    const GROUP_CATEGORY_LABEL_PREFIX  = 'platformd.groups.category.';

    static private $validCategories = array(
        'location',
        'product',
        'topic',
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
     * @var string $name
     * @Assert\NotNull
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string $category
     * @Assert\NotNull
     * @ORM\Column(name="category", type="string", length=50)
     */
    private $category;

    /**
     * @var text $description
     * @Assert\NotNull
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var text $howToJoin
     * @Assert\NotNull
     * @ORM\Column(name="howToJoin", type="text")
     */
    private $howToJoin;

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
     * @var boolean $isPublic
     * @Assert\NotNull
     * @ORM\Column(name="isPublic", type="boolean")
     */
    private $isPublic;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $backgroundImage;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $groupAvatar;

    /**
     * @var \Platformd\SpoutletBundle\Entity\Location
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\Location", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $location;

     /**
     * Holds the "many" locales relationship
     *
     * Don't set this directly, instead set "locales" directly, and a listener
     * will take care of properly creating the GamePageLocale relationship
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="GroupLocale", orphanRemoval=true, mappedBy="group")
     */
    private $groupLocales;

    private $locales;

    /**
     * @var boolean $allLocales
     * @Assert\NotNull
     * @ORM\Column(name="allLocales", type="boolean")
     */

    private $allLocales;

    /**
     * @var boolean $deleted
     * @Assert\NotNull
     * @ORM\Column(name="deleted", type="boolean")
     */

    private $deleted;

    /**
     * The person who uploaded this media
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", cascade={"delete"})
     * @ORM\JoinColumn(onDelete="cascade")
     */
    protected $owner;

    public function __construct()
    {
        $this->groupLocales = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set category
     *
     * @param string $category
     */
    public function setCategory($category)
    {
        if (!in_array($category, self::$validCategories)) {
            throw new \InvalidArgumentException(sprintf('Invalid group category "%s" given', $category));
        }

        $this->category = $category;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    public static function getValidCategories()
    {
        return self::$validCategories;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set howToJoin
     *
     * @param text $howToJoin
     */
    public function setHowToJoin($howToJoin)
    {
        $this->howToJoin = $howToJoin;
    }

    /**
     * Get howToJoin
     *
     * @return text
     */
    public function getHowToJoin()
    {
        return $this->howToJoin;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;
    }

    /**
     * Get isPublic
     *
     * @return boolean
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set allLocales
     *
     * @param boolean $allLocales
     */
    public function setAllLocales($allLocales)
    {
        $this->allLocales = $allLocales;
    }

    /**
     * Get allLocales
     *
     * @return boolean
     */
    public function getAllLocales()
    {
        return $this->allLocales;
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

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $backgroundImage
     */
    public function setBackgroundImage(Media $backgroundImage = null)
    {
        $this->backgroundImage = $backgroundImage;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getGroupAvatar()
    {
        return $this->groupAvatar;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $groupAvatar
     */
    public function setGroupAvatar(Media $groupAvatar = null)
    {
        $this->groupAvatar = $groupAvatar;
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

    /**
     * @return \Platformd\SpoutletBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Location $location
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
    }



    public function getJoinedLocales()
    {
        return $this->getGroupLocales();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGroupLocales()
    {
        return $this->groupLocales;
    }

    /**
     * A funny function where you create a new Instance of whatever the
     * entities actual JoinedLocaleInterface is. You'll typically also
     * need to set the relationship on that new object back to this object:
     *
     *     $newGroupLocale = new GroupLocale();
     *     $newGroupLocale->setGroup($this);
     *
     *     return $newGroupLocale;
     *
     * @return \Platformd\SpoutletBundle\Locale\JoinedLocaleInterface
     */
    public function createJoinedLocale()
    {
        $newGroupLocale = new GroupLocale();
        $newGroupLocale->setGroup($this);

        return $newGroupLocale;
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
            'id' => $this->getId()
        );
    }

     public function getLocales()
    {
        return $this->areLocalesInitialized() ? $this->locales : array();
    }

    /**
     * The locales are null until someone actually sets them
     *
     * This allows us to set them on load of the entity based on the relationship,
     * but by checking this, we can be careful not to run over real values
     *
     * @return bool
     */
    public function areLocalesInitialized()
    {
        return is_array($this->locales);
    }

    public function setLocales(array $locales)
    {
        $this->locales = $locales;

        // force Doctrine to see this as dirty
        $this->updatedAt = new \DateTime();
    }



    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $owner
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }
}
