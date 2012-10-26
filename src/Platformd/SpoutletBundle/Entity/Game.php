<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\ExecutionContext;

/**
 * Platformd\SpoutletBundle\Entity\Game
 *
 * @ORM\Table(name="pd_game")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GameRepository")
 * @Assert\Callback(methods={"validateGameCategory"})
 */
class Game
{
    const GAME_CATEGORY_LABEL_PREFIX    = 'platformd.admin.games.category.';
    const GAME_SUBCATEGORY_LABEL_PREFIX = 'platformd.admin.games.subcategory.';

    static private $validCategories = array(
        'action',
        'rpg',
        'strategy',
        'other',
    );

    static private $validSubcategories = array(
        'free-to-play',
        'mmo',
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
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @var string $category
     *
     * @ORM\Column(name="category", type="string", length=50)
     * @Assert\NotNull
     */
    private $category;

    /**
     * @var string $subcategory
     *
     * @ORM\Column(name="subcategories", type="array")
     */
    private $subcategories = array();

    /**
     * @var string $facebookFanpageUrl
     *
     * @ORM\Column(name="facebookFanpageUrl", type="string", length=255, nullable=true)
     */
    private $facebookFanpageUrl;

    /**
     * The fullysize logo for the game
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $logo;

    /**
     * The logo thumbnail for the game
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $logoThumbnail;

    /**
     * An image that contains the logos for the individual publisher/developer logos
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $publisherLogos;

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
     * @return string
     */
    public function getSubcategories()
    {
        return $this->subcategories;
    }

    /**
     * @param array $subcategories
     */
    public function setSubcategories(array $subcategories)
    {
        foreach ($subcategories as $subcategory) {
            if (!in_array($subcategory, self::$validSubcategories)) {
                throw new \InvalidArgumentException(sprintf('Invalid game subcategory "%s" given', $subcategory));
            }
        }

        $this->subcategories = $subcategories;
    }

    public static function getValidSubcategories()
    {
        return self::$validSubcategories;
    }

    /**
     * Set facebookFanpageUrl
     *
     * @param string $facebookFanpageUrl
     */
    public function setFacebookFanpageUrl($facebookFanpageUrl)
    {
        $this->facebookFanpageUrl = $facebookFanpageUrl;
    }

    /**
     * Get facebookFanpageUrl
     *
     * @return string
     */
    public function getFacebookFanpageUrl()
    {
        return $this->facebookFanpageUrl;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $logo
     */
    public function setLogo(Media $logo = null)
    {
        $this->logo = $logo;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getPublisherLogos()
    {
        return $this->publisherLogos;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $publisherLogos
     */
    public function setPublisherLogos(Media $publisherLogos = null)
    {
        $this->publisherLogos = $publisherLogos;
    }

    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getLogoThumbnail()
    {
        return $this->logoThumbnail;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $logoThumbnail
     */
    public function setLogoThumbnail(Media $logoThumbnail = null)
    {
        $this->logoThumbnail = $logoThumbnail;
    }

    /**
     * Category/Genre is a required field so ensure that it is present and valid in order to add game
     *
     *
     * @param \Symfony\Component\Validator\ExecutionContext $executionContext
     */
    public function validateGameCategory(ExecutionContext $executionContext)
    {
        // error if invalid or no category is specified

        if (in_array($this->category, self::$validCategories)) {
            return;
        }

        $propertyPath = $executionContext->getPropertyPath() . '.category';
        $executionContext->setPropertyPath($propertyPath);

        $executionContext->addViolation(
            "Please select a valid category for this game",
            array(),
            "category"
        );
    }
}
