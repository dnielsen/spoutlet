<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Sweepstakes;
use Gedmo\Mapping\Annotation as Gedmo;

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
}
