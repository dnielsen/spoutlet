<?php

namespace Platformd\NewsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\MediaBundle\Entity\Media;
use Platformd\GameBundle\Entity\Game as Game;
use Platformd\SearchBundle\Model\IndexableInterface;
use Platformd\TagBundle\Model\TaggableInterface;

/**
 * Platformd\NewsBundle\Entity\News
 *
 * @ORM\Table(name="sp_news")
 * @ORM\Entity(repositoryClass="Platformd\NewsBundle\Entity\NewsRepository")
 */
class News implements LinkableInterface, IndexableInterface, TaggableInterface
{

    CONST NEWS_TYPE_ARTICLE = 'article';
    CONST NEWS_TYPE_NEWS    = 'news';

    private static $validTypes = array(
        self::NEWS_TYPE_NEWS,
        self::NEWS_TYPE_ARTICLE,
    );

    const SEARCH_PREFIX  = 'news_';


    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $title
     *
     * @Assert\NotBlank
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var text $body
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * @var string
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     *
     * The following is duplicated in AbstractEvent
     * @Assert\Regex(pattern="/^[A-Za-z0-9\-]+$/", message="This can only contain letters, numbers and dashes (-)")
     *      Only allow numbers, digits and dashes
     */
    private $slug;

    /**
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $image;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length="2", nullable=true)
     */
    protected $locale;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="pd_news_site")
     */
     private $sites;

    /**
     * @var boolean $published
     *
     * @ORM\Column(name="published", type="boolean")
     */
    protected $published = false;

    /**
     * @var \DateTime $postedAt
     *
     * @Assert\NotBlank
     * @ORM\Column(type="date")
     */
    protected $postedAt;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url
     */
    protected $overrideUrl;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $blurb;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GameBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Game
     */
    protected $game;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\NotNull()
     */
    protected $type = self::NEWS_TYPE_ARTICLE;

    /**
     * @var Platformd\TagBundle\Entity\Tag[]
     *
     */
    private $tags;
    /**
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $thumbnail;

    public function __construct()
    {
        $this->sites = new ArrayCollection();
    }

    /**
    * @ORM\Column(name="cevo_article_id", type="integer", nullable=true)
     */
    private $cevoArticleId;

    public function __construct()
    {
        $this->sites = new ArrayCollection();
        $this->postedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function setPublished($published)
    {
        $this->published = $published;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function getPostedAt()
    {
        return $this->postedAt;
    }

    public function setPostedAt($postedAt)
    {
        $this->postedAt = $postedAt;
    }

    public function getOverrideUrl()
    {
        return $this->overrideUrl;
    }

    public function setOverrideUrl($overrideUrl)
    {
        $this->overrideUrl = $overrideUrl;
    }

    public function getBlurb()
    {
        return $this->blurb;
    }

    public function setBlurb($blurb)
    {
        $this->blurb = $blurb;
    }

    public function getLinkableOverrideUrl()
    {
        return $this->getOverrideUrl();
    }

    public function getLinkableRouteName()
    {
        return 'news_show';
    }

    public function getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug(),
        );
    }

    public function getCommentThreadId()
    {
        // requires locale to be kept in for BC - for new posts, this results in the thread ID having two "-", e.g. "news--124"
        return sprintf('news-%s-%s', $this->getLocale(), $this->getId());
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage(Media $image = null)
    {
        $this->image = $image;
    }

    public function setGame($game)
    {
        $this->game = $game;
    }

    public function getGame()
    {
        return $this->game;
    }

    public function getThreadId()
    {
        return $this->getCommentThreadId();
    }

    public function getSearchEntityType()
    {
        return 'news';
    }

    public function getSearchFacetType()
    {
        return 'news';
    }

    public function getSearchId()
    {
        return self::SEARCH_PREFIX.$this->id;
    }

    public function getSearchTitle()
    {
        return $this->title;
    }

    public function getSearchBlurb()
    {
        return $this->blurb ?: $this->body;
    }

    public function getSearchDate()
    {
        return $this->postedAt;
    }

    public function getDeleteSearchDocument()
    {
        return false == $this->published;
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function getTaggableType()
    {
        return 'platformd_news';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }

    public function setType($value)
    {
        if ($value && !in_array($value, self::$validTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid type "%s" given', $value));
        }

        $this->type = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public static function getTypes()
    {
        return self::$validTypes;
    }

    public function setThumbnail($value)
    {
        $this->thumbnail = $value;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setCevoArticleId($value)
    {
        $this->cevoArticleId = $value;
    }

    public function getCevoArticleId()
    {
        return $this->cevoArticleId;
    }
}
