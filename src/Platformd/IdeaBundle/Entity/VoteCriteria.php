<?php
namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use \DateTime;


/**
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\VoteCriteriaRepository")
 * @ORM\Table(name="vote_criteria")
 */
class VoteCriteria
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $displayName;

    /**
     * @ORM\Column(type="string", length=1000)
     */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="Vote", mappedBy="criteria")
     */
    protected $votes;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent")
     */
    protected $event;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
    	$this->id = $id;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     * @return VoteCriteria
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return VoteCriteria
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
    public function __construct()
    {
        $this->votes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add votes
     *
     * @param Platformd\IdeaBundle\Entity\Vote $votes
     */
    public function addVote(\Platformd\IdeaBundle\Entity\Vote $votes)
    {
        $this->votes[] = $votes;
    }

    /**
     * Get votes
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getVotes()
    {
        return $this->votes;
    }

    public function getEvent()
    {
        return $this->$event;
    }
    public function setEvent($event)
    {
        $this->event = $event;
    }
}
