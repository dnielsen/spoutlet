<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\AbstractVote;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Comment;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="commenting_vote", indexes={@ORM\index(name="votetype_idx", columns={"vote_type"})})
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\CommentVoteRepository")
 */

class CommentVote extends AbstractVote
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Comment")
     * @ORM\JoinColumn(onDelete="cascade", nullable="false")
     */
    protected $comment;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="cascade", nullable="false")
     */
    protected $user;

    /**
     * @ORM\Column(name="ip_address", type="string", length="20")
     */
    protected $ipAddress;

    /**
     * @ORM\Column(name="vote_type", type="string", length="255")
     */
    protected $voteType;

    /**
     * @ORM\Column(name="voted_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $votedAt;

    public function __construct(User $user, Comment $comment, $voteType, $ipAddress)
    {
        $this->user         = $user;
        $this->comment      = $comment;
        $this->voteType     = $voteType;
        $this->ipAddress    = $ipAddress;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }
}

