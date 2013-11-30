<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile as UploadedFile;

/**
 * @ORM\Entity
 */
class Document
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $path;

    /**
     * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/jpg", "image/png", "image/gif"})
     */
    protected $file;

    /**
     * @ORM\OneToOne(targetEntity="Idea", mappedBy="image")
     */
    protected $idea;

    public function upload($ideaId)
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }

        $fileName = $this->getIdeaImageFileName($ideaId, $this->getFile()->getClientOriginalName());

        $this->getFile()->move(
            $this->getUploadRootDir(),
            $fileName
        );

        // set the path property to the filename where you've saved the file
        $this->path = $fileName;

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }


    // TODO: Check for type, size, etc.
    public function isValid()
    {
        if ( $this->getFile() == null ) {
            return false;
        }

        $allowedMimeTypes = array("image/jpeg", "image/jpg", "image/png", "image/gif");
        $mimeType = $this->getFile()->getMimeType();

        if (in_array($mimeType, $allowedMimeTypes))
        {
            return true;
        }

        return false;
    }

    public function delete()
    {
        if(file_exists($this->file))
        {
            if(is_writable($this->file)){
                unlink($this->file);
            }
        }
    }

    protected function getIdeaImageFileName($ideaId, $originalName)
    {
        return $ideaId.'.'.pathinfo($originalName, PATHINFO_EXTENSION);
    }



    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/images';
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

    /**
     * Set path
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set idea
     *
     * @param Platformd\IdeaBundle\Entity\Idea $idea
     */
    public function setIdea(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        $this->idea = $idea;
    }

    /**
     * Get idea
     *
     * @return Platformd\IdeaBundle\Entity\Idea
     */
    public function getIdea()
    {
        return $this->idea;
    }
}
