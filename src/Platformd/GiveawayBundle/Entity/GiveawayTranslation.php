<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * @ORM\Entity
 */
class GiveawayTranslation
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
     * @ORM\Column(type="array")
     */
    protected $redemptionInstructions;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @Assert\NotNull
     */
    protected $locale;

    /**
     * @ORM\ManyToOne(targetEntity="Giveaway", inversedBy="translations")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\NotNull
     */
    protected $translatable;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $backgroundImagePath;

    /**
     * @Assert\File(
     *   maxSize="6000000",
     *   mimeTypes={"image/png", "image/jpeg", "image/jpg", "image/gif"}
     * )
     */
    protected $backgroundImage;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Url
     */
    protected $backgroundLink;

    /**
     * @ORM\Column(name="bannerImage", type="string", length=255, nullable=true)
     */
    protected $bannerImage;

    /**
     * @Assert\File(
     *   maxSize="6000000",
     *   mimeTypes={"image/png", "image/jpeg", "image/jpg", "image/gif"}
     * )
     */
    protected $bannerImageFile;

    protected $removeBannerImage = false;

    protected $removeBackgroundImage = false;

    public function __construct(Site $locale = null, Giveaway $translatable = null)
    {
        $this->locale = $locale;
        $this->translatable = $translatable;
        $this->redemptionInstructions = array_fill(0, 6, '');
    }

    public function setTranslatable(Giveaway $translatable)
    {
        $this->translatable = $translatable;
    }

    /**
     * @return string
     */
    public function getRedemptionInstructions()
    {
        return $this->redemptionInstructions;
    }

    /**
     * @deprecated prefer getRedemptionInstructions instead
     **/
    public function getRedemptionInstructionsArray()
    {
        return $this->redemptionInstructions;
    }

    /**
     * @param string $redemptionInstructions
     */
    public function setRedemptionInstructions(array $redemptionInstructions)
    {
        $this->redemptionInstructions = $redemptionInstructions;
    }

    public function setRedemptionInstructionsArray(array $redemptionInstructions)
    {
        $this->redemptionInstructions = $redemptionInstructions;
    }

    public function getCleanedRedemptionInstructionsArray()
    {
        return array_filter($this->redemptionInstructions, function($instruction) {
            return !empty($instruction);
        });
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale(Site $site)
    {
        $this->locale = $site;
    }

    public function getBackgroundImagePath()
    {
        return $this->backgroundImagePath;
    }

    public function setBackgroundImagePath($backgroundImagePath)
    {
        $this->backgroundImagePath = $backgroundImagePath;
    }

    public function getBackgroundLink()
    {
        return $this->backgroundLink;
    }

    public function setBackgroundLink($backgroundLink)
    {
        $this->backgroundLink = $backgroundLink;
    }

    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    public function setBackgroundImage($backgroundImage)
    {
        $this->backgroundImage = $backgroundImage;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBannerImage()
    {
        return $this->bannerImage;
    }

    public function setBannerImage($bannerImage)
    {
        $this->bannerImage = $bannerImage;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\File
     */
    public function getBannerImageFile()
    {
        return $this->bannerImageFile;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\File $bannerImageFile
     */
    public function setBannerImageFile($bannerImageFile)
    {
        $this->bannerImageFile = $bannerImageFile;
    }

    public function getRemoveBackgroundImage()
    {
        return $this->removeBackgroundImage;
    }

    public function setRemoveBackgroundImage($removeBackgroundImage)
    {
        $this->removeBackgroundImage = $removeBackgroundImage;
    }

    public function getRemoveBannerImage()
    {
        return $this->removeBannerImage;
    }

    public function setRemoveBannerImage($removeBannerImage)
    {
        $this->removeBannerImage = $removeBannerImage;
    }
}

