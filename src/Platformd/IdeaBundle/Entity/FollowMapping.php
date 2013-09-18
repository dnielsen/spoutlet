<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Follower
 * @ORM\Table(name="followMappings")
 * @ORM\Entity
 */
class FollowMapping
{
    /**
     * @ORM\Id
     * @ORM\Column(name="user", type="string", length=100)
     */
    protected $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Idea", inversedBy="followMappings")
     * @ORM\JoinColumn(name="idea", referencedColumnName="id")
     */
    protected $idea;


    /**
     * Constructor
     */
    public function __construct($userName, $idea)
    {
        $this->user = $userName;
        $this->idea = $idea;
    }


    /**
     * Set user
     *
     * @param string $user
     * @return FollowMapping
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Set idea
     *
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     * @return FollowMapping
     */
    public function setIdea(\Platformd\IdeaBundle\Entity\Idea $idea = null)
    {
        $this->idea = $idea;

        return $this;
    }

    /**
     * Get idea
     *
     * @return \Platformd\IdeaBundle\Entity\Idea
     */
    public function getIdea()
    {
        return $this->idea;
    }
}
