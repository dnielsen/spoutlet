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

    public function __construct($tagName)
    {
        $this->tagName = $tagName;
        $this->ideas = new ArrayCollection();
    }


    /**
     * Get tag name
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }


    /**
     * Add idea, synchronously update idea with tag
     *
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     * @return Tag
     */
    public function addIdeaCascade(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        if (!$this->hasIdea($idea)){
            $this->ideas[] = $idea;
            $idea->addTag($this);
        }

        return $this;
    }

    /**
     * Add idea
     *
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     * @return Tag
     */
    public function addIdea(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        if (!$this->hasIdea($idea)){
            $this->ideas[] = $idea;
        }

        return $this;
    }


    /**
     * Remove idea, synchronously remove this tag from the idea
     *
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     */
    public function removeIdeaCascade(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        if ($this->hasIdea($idea))
        {
            $this->ideas->removeElement($idea);
            $idea->removeTag($this);
        }
    }

    /**
     * Remove idea
     *
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     */
    public function removeIdea(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        if ($this->hasIdea($idea))
        {
            $this->ideas->removeElement($idea);
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
            $this->addIdeaCascade($idea);
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

    /**
     * Set tagName
     *
     * @param string $tagName
     * @return Tag
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;

        return $this;
    }
}
