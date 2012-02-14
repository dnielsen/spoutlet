<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\AbstractEventRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "event"     = "Platformd\SpoutletBundle\Entity\Event",
 *      "giveaway"  = "Platformd\GiveawayBundle\Entity\Giveaway"
 * })
 */
class AbstractEvent
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string $slug
     *
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(name="slug", type="string", length=255)
     */
    protected $slug;

    /**
     * @var boolean $ready
     *
     * @deprecated I don't think this field was ever used
     * @ORM\Column(name="ready", type="boolean")
     */
    protected $ready = true;

    /**
     * @var text $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length="2", nullable=false)
     * @Assert\NotBlank
     */
    protected $locale;

    /**
     * @ORM\Column(name="bannerImage", type="string", length=255, nullable=true)
     */
    protected $bannerImage;

    /**
     * @Assert\File(
        maxSize="6000000",
        mimeTypes={"image/png", "image/jpeg", "image/jpg"}
     * )
     */
    protected $bannerImageFile;

    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var datetime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
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
     * Set ready
     *
     * @deprecated I don't think this was ever used
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }

    /**
     * Get ready
     *
     * @deprecated I don't think this was ever used
     * @return boolean 
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
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

    protected function getUploadRootDir()
    {

        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {

        return '/uploads/events';
    }

    public function getAbsolutePath()
    {
        if (!$this->bannerImage) {

            return null;
        }

        return $this->getUploadRootDir().'/'.$this->bannerImage;
    }

    public function getWebPath()
    {
        return null === $this->bannerImage ? null : $this->getUploadDir().'/'.$this->bannerImage;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated(DateTime $updated)
    {
        $this->updated = $updated;
    }
}