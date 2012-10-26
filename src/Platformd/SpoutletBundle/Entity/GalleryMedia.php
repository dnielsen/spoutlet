<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Platformd\MediaBundle\Entity\GalleryMedia
 *
 * @ORM\Table(name="pd_gallery_media")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GalleryMediaRepository")
 */
class GalleryMedia
{

    static private $validCategories = array(
        'image',
        'video',
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
     * @var string $category
     * @ORM\Column(name="category", type="string", length=50)
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity="GalleryImage")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $image = null;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Gallery")
     * @ORM\JoinTable(name="pd_gallery_media_galleries")
     */
    private $galleries;

    public function __construct()
    {
        $this->galleries = new ArrayCollection();
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

    public function setCategory($category)
    {
        if (!in_array($category, self::$validCategories)) {
            throw new \InvalidArgumentException(sprintf('Invalid group category "%s" given', $category));
        }

        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getGalleries()
    {
        return $this->galleries;
    }

    public function setGalleries($galleries)
    {
        $this->galleries = $galleries;
    }

    public static function getValidCategories()
    {
        return self::$validCategories;
    }
}
