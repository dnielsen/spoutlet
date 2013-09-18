<?php
namespace Platformd\IdeaBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="comments")
 */
class Comment
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="comments")
     */
    protected $user;
    /**
     * @ORM\Column(type="string", length=1000)
     */
    protected $text;
    /**
     * @ORM\Column(type="datetime")
     */
    protected $timestamp;
    /**
     * @ORM\ManyToOne(targetEntity="Idea", inversedBy="comments")
     * @ORM\JoinColumn(name="idea_id", referencedColumnName="id")
     */
    protected $idea;

    public function __construct($user, $text, $idea)
    {
        $this->user = $user;
        $this->text = $text;
        $this->timestamp = new \DateTime();
        $this->idea = $idea;
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
     * Set user
     *
     * @param string $user
     * @return Comment
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return Comment
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
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
     * Get idea
     *
     * @return Idea
     */
    public function getIdea()
    {
        return $this->idea;
    }

    /**
     * Set idea
     *
     * @param Idea $idea
     * @return Comment
     */
    public function setIdea(Idea $idea = null)
    {
        $this->idea = $idea;

        return $this;
    }

    /**
     * Get formatted timestamp
     *
     * @return string DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp->format('l M jS, Y g:i a');
    }

    /**
     * Set timestamp
     *
     * @return Comment
     */
    public function setTimestamp()
    {
        $this->timestamp = new \DateTime();

        return $this;
    }

}
