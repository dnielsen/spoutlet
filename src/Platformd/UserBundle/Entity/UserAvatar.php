<?php

namespace Platformd\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Platformd\UserBundle\Entity\UserAvatarRepository")
 **/
class UserAvatar
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
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="userAvatars")
     **/
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     **/
    protected $media;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isApproved = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isSelected = false;

    /**
     * Get id.
     *
     * @return id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get user.
     *
     * @return user.
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     * @param user the value to set.
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Get media.
     *
     * @return media.
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set media.
     *
     * @param media the value to set.
     */
    public function setMedia($media)
    {
        $this->media = $media;
    }

    /**
     * Get isApproved.
     *
     * @return isApproved.
     */
    public function isApproved()
    {
        return $this->isApproved;
    }

    /**
     * Set isApproved.
     *
     * @param isApproved the value to set.
     */
    public function setApproved($isApproved)
    {
        $this->isApproved = $isApproved;
    }

    /**
     * Get isSelected.
     *
     * @return isSelected.
     */
    public function isSelected()
    {
        return $this->isSelected;
    }

    /**
     * Set isSelected.
     *
     * @param isSelected the value to set.
     */
    public function setSelected($isSelected)
    {
        $this->isSelected = $isSelected;
    }

    public function toggleSelected()
    {
        $this->isSelected = !$this->isSelected;
    }

    public function toggleApproval()
    {
        $this->isApproved = !$this->isApproved;
    }
}

