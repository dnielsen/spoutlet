<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Tag
 *
 * @ORM\Table(name="tags")
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\TagRepository")
 */
class Tag
{
    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="string", length=100)
     * @ORM\Id
     */
    protected $tagName;

    /**
     * @ORM\ManyToMany(targetEntity="Idea", mappedBy="tags")
     */
    protected $ideas;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\EventBundle\Entity\EventSession", mappedBy="tags")
     */
    protected $sessions;

    public function __construct($tagName)
    {
        $this->tagName  = $tagName;
        $this->ideas    = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }


    /**
     * Get tag name
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }
    /**
     * Set tagName
     * @param string $tagName
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * Add idea
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     */
    public function addIdea(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        if (!$this->hasIdea($idea)) {
            $this->ideas[] = $idea;
        }
    }

    /**
     * Remove idea
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     */
    public function removeIdea(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        if ($this->hasIdea($idea)) {
            $this->ideas->removeElement($idea);
        }
    }

    public function addSession ($session)
    {
        if (!$this->hasSession($session)) {
            $this->sessions[] = $session;
        }
    }

    public function removeSession($session)
    {
        if ($this->hasSession($session)) {
            $this->sessions->removeElement($session);
        }
    }

    /**
     * Add several ideas
     *
     * @param Array of ideas $ideas
     */
     public function addIdeas($ideas)
     {
        foreach ($ideas as $idea)
        {
            $this->addIdea($idea);
            $idea->addTag($this);
        }
    }

    /**
     * Get ideas
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdeas()
    {
        return $this->ideas;
    }


    /**
     * Get array of idea names

     * @return Array of strings $ideaNames
     */
    public function getIdeaNames()
    {
        $ideaNames = array();
        foreach ($this->ideas as $idea)
        {
            $ideaNames[] = $idea->getName();
        }
        return $ideaNames;
    }

    public function getSessionNames()
    {
        $sessionNames = array();
        foreach ($this->sessions as $session)
        {
            $sessionNames[] = $session->getName();
        }
        return $sessionNames;
    }

    /**
     * Check if an idea is associated with the tag
     *
     * @param $idea
     * @return Boolean
     */
    public function hasIdea($idea)
    {
        return in_array($idea->getName(), $this->getIdeaNames());
    }
    public function hasSession($session)
    {
        return in_array($session->getName(), $this->getSessionNames());
    }

}
