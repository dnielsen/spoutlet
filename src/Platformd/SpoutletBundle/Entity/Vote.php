<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\VoteRepository")
 * @ORM\Table(name="pd_vote", uniqueConstraints={@ORM\UniqueConstraint(name="media_user_idx", columns={"galleryMedia_id", "user_id"})})
 */
class Vote
{
    const VOTE_TYPE_UP = 'up';
    const VOTE_TYPE_DOWN = 'down';

    private static $validVoteTypes = array(
        self::VOTE_TYPE_UP,
        self::VOTE_TYPE_DOWN,
    );

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\GalleryMedia")
     * @ORM\JoinColumn(onDelete="cascade", nullable="false")
     */
    private $galleryMedia;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="cascade", nullable="false")
     */
    protected $user;

    /**
     * e.g. "up", "down" to denote how the vote was placed
     *
     * @ORM\Column(name="vote_type", type="string", length="255")
     */

    private $voteType = false;

    /**
     * @ORM\Column(name="voted_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $votedAt;

    public function getId()
    {
        return $this->id;
    }

    public function setGallerymedia($galleryMedia)
    {
        $this->galleryMedia = $galleryMedia;
    }

    public function getGallerymedia()
    {
        return $this->galleryMedia;
    }

    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setContest($contest)
    {
        $this->contest = $contest;
    }

    public function getContest()
    {
        return $this->contest;
    }

    public function setVoteType($voteType)
    {
        if (!in_array($voteType, self::$validVoteTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid vote type "%s" given', $voteType));
        }

        $this->voteType = $voteType;
    }

    public function getVoteType()
    {
        return $this->voteType;
    }


    public function setVotedAt($votedAt)
    {
        $this->votedAt = $votedAt;
    }

    public function getVotedAt()
    {
        return $this->votedAt;
    }

    public static function getValidVoteTypes()
    {
        return self::$validVoteTypes;
    }
}


