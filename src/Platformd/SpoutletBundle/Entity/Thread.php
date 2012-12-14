<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\Thread
 *
 * @ORM\Table(name="comment_thread")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\ThreadRepository")
 */
class Thread 
{
	/**
     * @Assert\NotNull(message="Required")
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $id;

     /**
     * Tells if new comments can be added in this thread
     *
     * @var bool
     */
    protected $isCommentable = true;

    /**
     * Denormalized date of the last comment
     *
     * @var DateTime
     */
    protected $lastCommentAt = null;

    /**
     * Url of the page where the thread lives
     *
     * @var string
     */
    protected $permalink;

    protected $comments;
}