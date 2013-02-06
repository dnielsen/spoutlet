<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Platformd\SpoutletBundle\Entity\Site;

/**
 * @ORM\Entity
 */
class GroupEventTranslation
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    protected $name;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    protected $content;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @Assert\NotNull
     */
    protected $locale;

    /**
     * @ORM\ManyToOne(targetEntity="GroupEvent", inversedBy="translations")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\NotNull
     */
    protected $translatable;

    protected $currentLocale;

    protected $defaultLocale = 'en';

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

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
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
