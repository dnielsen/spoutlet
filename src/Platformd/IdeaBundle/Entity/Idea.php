<?php
namespace Platformd\IdeaBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\IdeaRepository")
 * @ORM\Table(name="idea")
 */
class Idea
{
	/**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
	protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent", inversedBy="ideas")
     */
    protected $event;

	/**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="ideas")
     */
    protected $creator;

	/**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=255, nullable="true")
     */
    protected $stage;

    /**
     * @ORM\Column(type="boolean", nullable="true")
     */
    protected $forCourse;

    /**
     * @ORM\Column(type="string", length=255, nullable="true")
     */
    protected $professors;

    /**
     * @ORM\Column(type="string", length=255, nullable="true")
     */
    protected $amount;

    /**
     * @ORM\Column(type="text", nullable="true")
     */
    protected $members;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="idea", cascade={"remove"})
     */
    protected $comments;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="ideas")
     * @ORM\JoinTable(name="TagIdeaMap",
     *      joinColumns={@ORM\JoinColumn(name="idea", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag", referencedColumnName="tag")}
     *      )
     */
    protected $tags;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable(name="JudgeIdeaMap",
     *      joinColumns={@ORM\JoinColumn(name="idea", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="judge", referencedColumnName="id", onDelete="cascade")}
     *      )
     */
    protected $judges;

    /**
     * @ORM\OneToMany(targetEntity="Vote", mappedBy="idea", cascade={"remove"})
     */
    protected $votes;

    /**
     * @ORM\OneToMany(targetEntity="FollowMapping", mappedBy="idea", cascade={"remove"})
     */
    protected $followMappings;

	/**
     * Highest voting round for which the idea has progressed.
     * If round == event.currentRound then the idea is still alive
     * @ORM\Column(type="integer")
     */
    protected $highestRound;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPrivate;

    /**
     * @ORM\OneToOne(targetEntity="Document", inversedBy="idea", cascade={"remove"})
     */
    protected $image;

    /**
     * @ORM\OneToMany(targetEntity="Link", mappedBy="idea", cascade={"remove"})
     */
    protected $links;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->followMappings = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->judges = new ArrayCollection();
        $this->isPrivate = false;
        $this->createdAt = new \DateTime();
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
     * Set name
     *
     * @param string $name
     * @return Idea
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getEvent()
    {
        return $this->event;
    }
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * Set name
     *
     * @param $creator
     * @return Idea
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get name
     *
     * @return user object
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Idea
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Add comment
     *
     * @param \Platformd\IdeaBundle\Entity\Comment $comment
     * @return Idea
     */
    public function addComment(\Platformd\IdeaBundle\Entity\Comment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \Platformd\IdeaBundle\Entity\Comment $comment
     */
    public function removeComment(\Platformd\IdeaBundle\Entity\Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set members
     *
     * @param string $members
     * @return Idea
     */
    public function setMembers($members)
    {
        $this->members = $members;

        return $this;
    }

    /**
     * Get members
     *
     * @return string
     */
    public function getMembers()
    {
        return $this->members;
    }


    /**
     * Add tag
     *
     * @param \Platformd\IdeaBundle\Entity\Tag $tags
     * @return Idea
     */
    public function addTag(\Platformd\IdeaBundle\Entity\Tag $tag)
    {
        if (!$this->hasTag($tag)){
            $this->tags[] = $tag;
        }

        return $this;
    }


    /**
     * Add tag, synchronously update tag with this idea
     *
     * @param \Platformd\IdeaBundle\Entity\Tag $tags
     * @return Idea
     */
    public function addTagCascade(\Platformd\IdeaBundle\Entity\Tag $tag)
    {
        if (!$this->hasTag($tag)){
            $this->tags[] = $tag;
            $tag->addIdea($this);
        }

        return $this;
    }


    /**
     * Add several tags
     *
     * @param Array of tags $tags
     */
     public function addTags($tags)
     {
        foreach ($tags as $tag)
        {
            $this->addTagCascade($tag);
        }
    }


    /**
     * Remove tag
     *
     * @param \Platformd\IdeaBundle\Entity\Tag $tag
     */
    public function removeTag(\Platformd\IdeaBundle\Entity\Tag $tag)
    {
        if ($this->hasTag($tag)){
            $this->tags->removeElement($tag);
        }
    }


    /**
     * Remove tag, synchronously remove this idea from the tag
     *
     * @param \Platformd\IdeaBundle\Entity\Tag $tag
     */
    public function removeTagCascade(\Platformd\IdeaBundle\Entity\Tag $tag)
    {
        if ($this->hasTag($tag)){
            $this->tags->removeElement($tag);
            $tag->removeIdea($this);
        }
    }


    /**
     * Removes all tags
     */
    public function removeAllTags()
    {
        foreach ($this->getTags() as $tag)
        {
            $this->removeTagCascade($tag);
        }
    }


    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }


    /**
     * Get array of tag names

     * @return Array of strings $tagNames
     */
     public function getTagNames()
     {
        $tagNames = array();
        foreach ($this->tags as $tag)
        {
            $tagNames[] = $tag->getTagName();
        }
        return $tagNames;
    }


    /**
     * Get string of tags (to populate twig template for edit page)
     *
     * @return imploded string of tag names
     */
    public function getImplodedTagString()
    {
        return implode(" ", $this->getTagNames());
    }


    /**
     * Check if a tag is associated with the idea
     *
     * @param $tag
     * @return Boolean
     */
    public function hasTag($tag)
    {
        return in_array($tag->getTagName(), $this->getTagNames());
    }


    /**
     * Add vote
     *
     * @param \Platformd\IdeaBundle\Entity\Vote $vote
     * @return Idea
     */
    public function addVote(\Platformd\IdeaBundle\Entity\Vote $vote)
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove votes
     *
     * @param \Platformd\IdeaBundle\Entity\Vote $votes
     */
    public function removeVote(\Platformd\IdeaBundle\Entity\Vote $votes)
    {
        $this->votes->removeElement($votes);
    }

    /**
     * Get votes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVotes()
    {
        return $this->votes;
    }

    public function getVoteAvg($criteria = null) {
    	$count = 0;
    	$sum = 0;
    	foreach($this->votes as $vote) {
    		if($criteria == null) {
    			$count++;
    			$sum += $vote->getvalue();
    		} elseif($vote->getCriteria() == $criteria) {
    			$count++;
    			$sum += $vote->getvalue();
    		}
    	}

    	if($count == 0)
    		return 0;
    	return number_format($sum / $count, 2);
    }


    /**
     * Add followMapping
     *
     * @param userName
     * @return Idea
     */
    public function addFollowMapping($followMapping)
    {
        $this->followMappings[] = $followMapping;
        return $this;
    }

    /**
     * Remove followMapping
     *
     * @param \Platformd\IdeaBundle\Entity\FollowMapping $followMapping
     */
    public function removeFollowMapping($followMapping)
    {
        $this->followMappings->removeElement($followMapping);
    }

    /**
     * Get followMappings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFollowMappings()
    {
        return $this->followMappings;
    }

    /**
     * @param $userName
     * @return null
     */
    public function getFollowMapping($userName)
    {
        $followMappings = $this->getFollowMappings();
        foreach ($followMappings as $followMapping)
        {
            if($followMapping->getUser() == $userName)
                return $followMapping;
        }
        return null;
    }

    /**
     * Get numFollowers
     *
     * @return integer
     */
    public function getNumFollowers()
    {
        return $this->getFollowMappings()->count();
    }

    /**
     * Is the currently logged in user following this idea
     *
     * @return boolean
     */
    public function isUserFollowing($userName)
    {
        if ($this->getFollowMapping($userName))
            return true;
        return false;
    }

    public function canUserView($user)
    {
        // public ideas are viewable by anyone
        if ($this->isPrivate == false){
            return true;
        }

        // private ideas

        // not logged in
        if ($user == null){
            return false;
        }
        // logged in as creator or admin or assigned judge
        if ($this->getCreator() == $user ||
            in_array('ROLE_SUPER_ADMIN', $user->getRoles()) ||
            $this->isJudgeAssigned($user)){
            return true;
        }

        // logged in as normal user
        return false;
    }

	/**
     * Set highestRound
     *
     * @param integer $highestRound
     * @return Idea
     */
    public function setHighestRound($highestRound)
    {
        $this->highestRound = $highestRound;

        return $this;
    }

    /**
     * Get highestRound
     *
     * @return integer
     */
    public function getHighestRound()
    {
        return $this->highestRound;
    }

    /**
     * Set isPrivate
     *
     * @param boolean $isPrivate
     */
    public function setIsPrivate($isPrivate)
    {
        $this->isPrivate = $isPrivate;
    }

    /**
     * Get isPrivate
     *
     * @return boolean
     */
    public function getIsPrivate()
    {
        return $this->isPrivate;
    }

    /**
     * Set image
     *
     * @param Platformd\IdeaBundle\Entity\Document $image
     */
    public function setImage(\Platformd\IdeaBundle\Entity\Document $image)
    {
        $this->image = $image;
    }

    /**
     * Get image
     *
     * @return Platformd\IdeaBundle\Entity\Document
     */
    public function getImage()
    {
        return $this->image;
    }

    public function removeImage()
    {
        $this->image = null;
    }

    /**
     * Add link
     *
     * @param Platformd\IdeaBundle\Entity\Link $link
     */
    public function addLink(\Platformd\IdeaBundle\Entity\Link $link)
    {
        $this->links[] = $link;
    }

    /**
     * Get links
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Remove link
     *
     * @param \Platformd\IdeaBundle\Entity\Link $link
     */
    public function removeLink(\Platformd\IdeaBundle\Entity\Link $link)
    {
        $this->links->removeElement($link);
    }

    /**
     * Set stage
     *
     * @param string $stage
     */
    public function setStage($stage)
    {
        $this->stage = $stage;
    }

    /**
     * Get stage
     *
     * @return string
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * Set forCourse
     *
     * @param boolean $forCourse
     */
    public function setForCourse($forCourse)
    {
        $this->forCourse = $forCourse;
    }

    /**
     * Get forCourse
     *
     * @return boolean
     */
    public function getForCourse()
    {
        return $this->forCourse;
    }

    /**
     * Set professors
     *
     * @param string $professors
     */
    public function setProfessors($professors)
    {
        $this->professors = $professors;
    }

    /**
     * Get professors
     *
     * @return string
     */
    public function getProfessors()
    {
        return $this->professors;
    }

    /**
     * Set amount
     *
     * @param string $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($time)
    {
        $this->createdAt = $time;
    }

    /**
     * Get judges
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getJudges()
    {
        return $this->judges;
}

    public function setJudges($judges) {
        $currentJudges = $this->getJudges();
        $currentJudges->clear();
        foreach($judges as $judge) {
            $currentJudges->add($judge);
        }
    }

    /**
     * Answers is the provided judge assigned to vote for this idea
     *
     * @param Platformd\UserBundle\Entity\User $judge
     */
    public function isJudgeAssigned(\Platformd\UserBundle\Entity\User $judge)
    {
        return $this->getJudges()->contains($judge);
    }
}