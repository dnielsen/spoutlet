<?php

namespace Platformd\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Platformd\UserBundle\Entity\GallaryRepository")
 * @ORM\Table(name="gallary")
 */
class Gallary
{
    const GALLARY_FILE_EXTENSION            = 'png';
    const GALLARY_DIRECTORY_PREFIX          = 'images/gallary';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="avatars")
     * @ORM\JoinColumn(onDelete="SET NULL")
     **/
    protected $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     **/
    protected $uuid;

    /**
     * @Assert\File(mimeTypes={"image/png", "image/jpeg", "image/jpg", "image/gif"})
     */
    public $file;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $approved = false;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $cropped = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $resized = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $processed = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $reviewed = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $deleted = false;

    /**
     * @ORM\Column(name="crop_dimensions", type="string", length=40, nullable=true)
     **/
    protected $cropDimensions;

    /**
     * @ORM\Column(name="initial_format", type="string", length=10, nullable=true)
     **/
    protected $initialFormat;

    /**
     * @ORM\Column(name="initial_width", type="integer", nullable=true)
     **/
    protected $initialWidth;

    /**
     * @ORM\Column(name="initial_height", type="integer", nullable=true)
     **/
    protected $initialHeight;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($value)
    {
        $this->user = $value;
    }

    public function getFilename()
    {
        return $this->uuid.'.initial.'.self::GALLARY_FILE_EXTENSION;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($value)
    {
        $this->uuid = $value;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function setMedia($value)
    {
        $this->media = $value;
    }

    public function isApproved()
    {
        return $this->approved;
    }

    public function setApproved($value)
    {
        $this->approved = $value;
    }

    public function toggleSelected()
    {
        $this->active = !$this->active;
    }

    public function toggleApproval()
    {
        $this->approved = !$this->approved;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($value)
    {
        $this->createdAt = $value;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($value)
    {
        $this->updatedAt = $value;
    }

    public function isCropped()
    {
        return $this->cropped;
    }

    public function setCropped($value)
    {
        $this->cropped = $value;
    }

    public function isResized()
    {
        return $this->resized;
    }

    public function setResized($value)
    {
        $this->resized = $value;
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    public function setProcessed($value)
    {
        $this->processed = $value;
    }

    public function isReviewed()
    {
        return $this->reviewed;
    }

    public function setReviewed($value)
    {
        $this->reviewed = $value;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    public function setDeleted($value)
    {
        $this->deleted = $value;
    }

    public function getCropDimensions()
    {
        return $this->cropDimensions;
    }

    public function setCropDimensions($value)
    {
        $this->cropDimensions = $value;
    }

    public function getInitialFormat()
    {
        return $this->initialFormat;
    }

    public function setInitialFormat($value)
    {
        $this->initialFormat = $value;
    }

    public function getInitialWidth()
    {
        return $this->initialWidth;
    }

    public function setInitialWidth($value)
    {
        $this->initialWidth = $value;
    }

    public function getInitialHeight()
    {
        return $this->initialHeight;
    }

    public function setInitialHeight($value)
    {
        $this->initialHeight = $value;
    }

    public function isUsable()
    {
        return $this->approved &&  $this->cropped && $this->resized && $this->processed && $this->reviewed && !$this->deleted;
    }
}
