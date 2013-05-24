<?php

namespace Platformd\VideoBundle\Entity;

use Platformd\SpoutletBundle\Entity\AbstractVote;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Comment;
use Platformd\VideoBundle\Entity\YoutubeVideo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="pd_youtube_votes")
 * @ORM\Entity(repositoryClass="Platformd\VideoBundle\Repository\YoutubeVoteRepository")
 */

class YoutubeVote extends AbstractVote
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\VideoBundle\Entity\YoutubeVideo")
     * @ORM\JoinColumn(onDelete="cascade", nullable="false")
     */
    protected $video;

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

    public function __construct(User $user, YoutubeVideo $video, $voteType, $ipAddress)
    {
        $this->user         = $user;
        $this->video        = $video;
        $this->voteType     = $voteType;
        $this->ipAddress    = $ipAddress;
    }

    public function getVideo()
    {
        return $this->video;
    }

    public function setVideo($value)
    {
        $this->video = $value;
    }
}
