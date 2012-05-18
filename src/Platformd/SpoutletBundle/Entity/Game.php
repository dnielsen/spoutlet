<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\Game
 *
 * @ORM\Table(
 *      name="pd_game",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="slug_unique",
 *              columns={"slug"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GameRepository")
 */
class Game
{
    const GAME_CATEGORY_LABEL_PREFIX = 'platformd.admin.games.category.';

    static private $validCategories = array(
        'action',
        'rpg',
        'strategy',
        'other',
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
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     * @Assert\Regex(pattern="/^[A-Za-z0-9\-]+$/", message="This can only contain letters, numbers and dashes (-)")]
     */
    private $slug;

    /**
     * @var string $category
     *
     * @ORM\Column(name="category", type="string", length=50)
     * @Assert\NotNull
     */
    private $category;

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
        // sets the, but only if it's blank
        // this is not meant to be smart enough to guarantee correct uniqueness
        // that will happen with validation
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($name);

            $this->setSlug($slug);
        }

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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        // don't let the slug be blanked out
        // this allows the user to not enter a slug in the form. The slug
        // will be generated from the name, but not overridden by that blank
        // slug value
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
     * Set category
     *
     * @param string $category
     */
    public function setCategory($category)
    {
        if (!in_array($category, self::$validCategories)) {
            throw new \InvalidArgumentException(sprintf('Invalid game category "%s" given', $category));
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
}
