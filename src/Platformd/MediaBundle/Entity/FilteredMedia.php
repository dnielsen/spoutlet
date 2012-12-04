<?php

namespace Platformd\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Platformd\MediaBundle\Entity\FilteredMediaRepository")
 * @ORM\Table(name="pd_filtered_media")
 */
class FilteredMedia
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
     * @ORM\Column(name="path", type="string", length="255", nullable=false)
     */
    protected $path;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove"})
     */
    protected $parent;

    public function getId()
    {
        return $this->id();
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}
