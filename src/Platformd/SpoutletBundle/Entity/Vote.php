<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\VoteRepository")
 * @ORM\Table(name="pd_vote", uniqueConstraints={@UniqueConstraint(name="media_user_idx", columns={"media_id", "user_id"})})
 */
class Vote
{

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\GalleryMedia")
     * @ORM\JoinColumn(onDelete="cascade")
     */
    private $galleryMedia;

    /**
     * The person who created this gallery
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", cascade={"delete"})
     * @ORM\JoinColumn(onDelete="cascade")
     */
    protected $user;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(name="voted_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $votedAt;

    public function getId()
    {
        return $this->id;
    }

    public function setGallerymedia(UserInterface $galleryMedia)
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

    public function setVotedAt($votedAt)
    {
        $this->votedAt = $votedAt;
    }

    public function getVotedAt()
    {
        return $this->votedAt;
    }
}

