<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\UserBundle\Entity\User;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SweepstakesBundle\Entity\Entry
 *
 * @ORM\Table(name="sweepstakes_entry")
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\EntryRepository")
 */
class Entry
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
     */
    protected $user;

    /**
     * @var \Platformd\SweepstakesBundle\Entity\Sweepstakes
     * @ORM\ManyToOne(targetEntity="Platformd\SweepstakesBundle\Entity\Sweepstakes", inversedBy="entries")
     */
    protected $sweepstakes;

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
     * @return \Platformd\SweepstakesBundle\Entity\Sweepstakes
     */
    public function getSweepstakes()
    {
        return $this->sweepstakes;
    }

    /**
     * @param \Platformd\SweepstakesBundle\Entity\Sweepstakes $sweepstakes
     */
    public function setSweepstakes(Sweepstakes $sweepstakes)
    {
        $this->sweepstakes = $sweepstakes;
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