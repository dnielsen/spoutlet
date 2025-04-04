<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\GroupBundle\Entity\Group;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Platformd\SpoutletBundle\Entity\Contest
 *
 * @ORM\Table(name="pd_contest_entry")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\ContestEntryRepository")
 */
class ContestEntry
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var \Platformd\SpoutletBundle\Entity\Contest
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Contest", inversedBy="entries")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $contest;

    /**
     * @var string $ipAddress
     *
     * @ORM\Column(name="ipAddress", type="string", length=255)
     */
    private $ipAddress;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $deleted = false;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    protected $deletedAt;


    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\GalleryMedia", mappedBy="contestEntry")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $medias;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\GroupBundle\Entity\Group")
     * @ORM\JoinTable(name="pd_contest_entry_groups", joinColumns={@ORM\JoinColumn(name="contest_entry_id", referencedColumnName="id")}, inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")})
     */
    private $groups;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->groups = new ArrayCollection();
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
     * Set ipAddress
     *
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Get ipAddress
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Contest
     */
    public function getContest()
    {
        return $this->contest;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Contest $contest
     */
    public function setContest(Contest $contest)
    {
        $this->contest = $contest;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function getMedias()
    {
        return $this->medias;
    }

    public function setMedias($medias)
    {
        $this->medias = $medias;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups($value)
    {
        $this->groups = $value;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function addMedia(GalleryMedia $value)
    {
        $this->medias->add($value);
    }

    public function addGroup(Group $value)
    {
        $this->medias->add($value);
    }
}
