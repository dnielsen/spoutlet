<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\OpenGraphOverride
 *
 * @ORM\Table(name="pd_opengraphoverride")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\OpenGraphOverrideRepository")
 */
class OpenGraphOverride
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="description", length="255", nullable=true)
     */
    private $description;

    /**
     * The thumbnail image
     *
     * @var \Platformd\MediaBundle\Entity\Media
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    private $thumbnail;

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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

     /**
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param \Platformd\MediaBundle\Entity\Media $thumbnail
     */
    public function setThumbnail(Media $thumbnail = null)
    {
        $this->thumbnail = $thumbnail;
    }
}
