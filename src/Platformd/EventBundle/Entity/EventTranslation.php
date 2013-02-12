<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Platformd\SpoutletBundle\Entity\Site;

/**
 * Base Event Translation
 *
 * @ORM\MappedSuperclass
 */
abstract class EventTranslation
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $content;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @Assert\NotNull
     */
    protected $locale;

    /**
     * You have to specify mapping for that property in your concrete class
     *
     * @var Event
     */
    protected $translatable;

    /**
     * Banner Image for event
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"all"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $bannerImage;

    public function __construct(Site $locale = null, Event $translatable = null)
    {
        $this->locale = $locale;
        $this->translatable = $translatable;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $bannerImage
     */
    public function setBannerImage($bannerImage)
    {
        $this->bannerImage = $bannerImage;
    }

    /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getBannerImage()
    {
        return $this->bannerImage;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTranslatable($translatable)
    {
        $this->translatable = $translatable;
    }

    public function getTranslatable()
    {
        return $this->translatable;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }
}
