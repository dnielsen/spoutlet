<?php

namespace Platformd\SpoutletBundle\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\MediaBundle\Entity\Media;

use Doctrine\ORM\Mapping as ORM;

/**
 * Platformd\SpoutletBundle\Entity\Group
 *
 * @ORM\Table(name="pd_groups")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupRepository")
 */
class Group
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
}
