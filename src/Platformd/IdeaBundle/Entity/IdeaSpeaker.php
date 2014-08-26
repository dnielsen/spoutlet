<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\Idea\Entity\IdeaSpeaker
 *
 * @ORM\Table(name="idea_speakers")
 * @ORM\Entity
 */
class IdeaSpeaker
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\IdeaBundle\Entity\Idea", inversedBy="speakers")
     */
    protected $idea;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $speaker;

    /**
     * Speaker Bio: Short introduction to audiance.
     *
     * @ORM\Column(name="biography", type="text", nullable=true)
     */
    protected $biography;

    /**
     * Speaker Role within session: Possilble values moderator, host, special guest, etc 
     *
     * @ORM\Column(name="role", type="text", nullable=true)
     */
    protected $role;

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
     * Set biography
     *
     * @param text $biography
     */
    public function setBiography($biography)
    {
        $this->biography = $biography;
    }

    /**
     * Get biography
     *
     * @return text 
     */
    public function getBiography()
    {
        return $this->biography;
    }

    /**
     * Set role
     *
     * @param text $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * Get role
     *
     * @return text 
     */
    public function getRole()
    {
        return $this->role;
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

    /**
     * Set speaker
     *
     * @param Platformd\UserBundle\Entity\User $speaker
     */
    public function setSpeaker(\Platformd\UserBundle\Entity\User $speaker)
    {
        $this->speaker = $speaker;
    }

    /**
     * Get speaker
     *
     * @return Platformd\UserBundle\Entity\User 
     */
    public function getSpeaker()
    {
        return $this->speaker;
    }
}