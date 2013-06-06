<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Site;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pd_gallery_translation")
 * @UniqueEntity(fields={"gallery", "site"}, message="This gallery already has a translation for this site.")
 */
class GalleryTranslation
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Gallery", inversedBy="translations")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $gallery;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable="true")
     */
    private $name;

    /**
     * * @ORM\JoinColumn(name="site_id")
     */
    private $siteId;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinColumn(name="site_id", onDelete="CASCADE")
     */
    private $site;

    public function getId()
    {
        return $this->id;
    }

    public function setGallery($value)
    {
        $this->gallery = $value;
    }

    public function getGallery()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value ?: '';
    }

    public function getName()
    {
        return $this->name;
    }

    public function setSiteId($value)
    {
        $this->siteId = $value;
    }

    public function getSiteId()
    {
        return $this->siteId;
    }

    public function setSite($value)
    {
        $this->site = $value;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function __toString()
    {
        return $this->name;
    }
}
