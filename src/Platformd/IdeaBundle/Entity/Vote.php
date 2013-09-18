<?php
namespace Platformd\IdeaBundle\Entity;

use Platformd\IdeaBundle\IdeaBundle;

use Doctrine\ORM\Mapping as ORM;
use \DateTime;


/**
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\VoteRepository")
 * @ORM\Table(name="Vote")
 */
class Vote
{
	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Idea", inversedBy="votes")
	 * */
	private $idea;

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="VoteCriteria", inversedBy="votes")
	 * */
	private $criteria;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=100)
	 * */
	private $voter;

	/** @ORM\Column(type="integer") */
	private $value;

	/** @ORM\Column(type="integer") */
	private $round;


	public function __construct($idea, $criteria, $currentRound) {
		$this->setIdea($idea);
		$this->setCriteria($criteria);
		$this->setRound($currentRound);
	}

    /**
     * Set voter
     *
     * @param string $voter
     * @return Vote
     */
    public function setVoter($voter)
    {
        $this->voter = $voter;

        return $this;
    }

    /**
     * Get voter
     *
     * @return string
     */
    public function getVoter()
    {
        return $this->voter;
    }

    /**
     * Set value
     *
     * @param integer $value
     * @return Vote
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return integer
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set idea
     *
     * @param \Platformd\IdeaBundle\Entity\Idea $idea
     * @return Vote
     */
    public function setIdea(\Platformd\IdeaBundle\Entity\Idea $idea)
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

    public function getIdeaId()
    {
    	return $this->getIdea()->getId();
    }

    /**
     * Set criteria
     *
     * @param \Platformd\IdeaBundle\Entity\VoteCriteria $criteria
     * @return Vote
     */
    public function setCriteria(\Platformd\IdeaBundle\Entity\VoteCriteria $criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Get criteria
     *
     * @return \Platformd\IdeaBundle\Entity\VoteCriteria
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    public function getCriteriaId()
    {
    	return $this->getCriteria()->getId();
    }

    /**
     * Set round
     *
     * @param integer $round
     * @return Vote
     */
    public function setRound($round)
    {
        $this->round = $round;

        return $this;
    }

    /**
     * Get round
     *
     * @return integer
     */
    public function getRound()
    {
        return $this->round;
    }
}
