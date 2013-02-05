<?php

namespace Knp\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Platformd\UserBundle\Entity\User;

/**
 * Knp\MediaBundle\Entity\Media
 *
 * @ORM\MappedSuperclass
 */
abstract class Media
{
    /**
     * @var string $filename
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    protected $filename;

    /**
     * A generic name for this media
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * Some generic description
     *
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var \Symfony\Component\HttpFoundation\File\File
     * @Assert\Image(groups = {"generic"})
     * @Assert\Image(
     *     maxSize = "2048k",
     *     maxSizeMessage = "Please upload an image that's 2 megabytes or smaller",
     *     groups = {"subject_image"}
     * )
     * @Assert\Image(mimeTypes={"image/jpeg", "image/jpg", "image/png", "image/gif"}, mimeTypesMessage="This is not a valid image file.")
     */
    protected $fileObject;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    /**
     * File size
     *
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $size;

    /**
     * File mime-type
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $mimeType;

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
     * Set filename
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function getFileObject()
    {
        return $this->fileObject;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\File\File $fileObject
     */
    public function setFileObject(File $fileObject)
    {
        $this->fileObject = $fileObject;

        // manually change this to trigger this record to look dirty
        // without this, the object won't go through its persistence lifecycle
        // and the processing of the new file object won't take place
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * @todo I'd like to record the original filename, but we can pretend for now
     * @return string
     */
    public function getOriginalFilename()
    {
        return $this->getFilename();
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param integer $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    public function __toString()
    {
        return (string) $this->getFilename();
    }
}
