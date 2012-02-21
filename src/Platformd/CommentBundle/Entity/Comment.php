<?php

namespace Platformd\CommentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\CommentBundle\Entity\Comment as BaseComment;
use Platformd\CommentBundle\Entity\Thread;
use FOS\CommentBundle\Model\SignedCommentInterface;
use FOS\CommentBundle\Model\VotableCommentInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use DateTime;
use DateTimezone;

/**
 * @ORM\Table(name="comment")
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Entity(repositoryClass="Platformd\CommentBundle\Entity\CommentRepository")
 * 
 */
class Comment extends BaseComment implements SignedCommentInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Thread of this comment
     *
     * @var Thread
     * @ORM\ManyToOne(targetEntity="Thread")
     */
    protected $thread;

    /**
     * Author of the comment
     *
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @var User
     */
    protected $author;

    /**
     * @return Thread
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * @param Thread $thread
     * @return null
     */
    public function setThread(Thread $thread)
    {
        $this->thread = $thread;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $author
     */
    public function setAuthor(UserInterface $author)
    {
        $this->author = $author;
    }

    /**
     * Helps the SignedCommentInterface - not needed, but default implementation
     * is just to return "Anonymous".
     *
     * @return string
     */
    public function getAuthorName()
    {
        return ($this->getAuthor()) ? $this->getAuthor()->getUsername() : parent::getAuthorName();
    }

    public function getParentId()
    {
        if (!$this->ancestors) {
            
            return null;
        }

        $ancestors = explode('/', $this->ancestors);
        return (int)array_pop($ancestors);
    }
    
    
    /**
    * Returns the end datetime converted into the timezone of the user
     *
    * @return \DateTime
    */
    public function getCreatedAtInTimezone()
    {
    return $this->convertDatetimeToTimezone($this->getCreatedAt());
    }
    
    /**
     * Returns an array that can be used in a template and passed to a translation string
     *
     * @return array
     */
    public function createdAtInTimezoneTranslationArray()
    {
        return self::convertDateTimeIntoTranslationArray($this->getCreatedAtInTimezone());
    }

    static private function convertDateTimeIntoTranslationArray(DateTime $dt)
    {
        return array(
            '%year%' => $dt->format('Y'),
            '%month%' => $dt->format('m'),
            '%day%' => $dt->format('d'),
            '%time%' => $dt->format('H:i'),
        );
    }

    private function convertDatetimeToTimezone(DateTime $dt)
    {
        $userTimezone = new DateTimeZone('UTC');
        $offset = $userTimezone->getOffset($dt);

        $timestamp = $dt->format('U') + $offset;

        return DateTime::createFromFormat('U', $timestamp, $userTimezone);
    }


}
