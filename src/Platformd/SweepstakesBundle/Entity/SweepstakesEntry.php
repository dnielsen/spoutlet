<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SweepstakesBundle\Entity\SweepstakesEntry
 *
 * @ORM\Table(name="sweepstakes_entry")
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepstakesEntryRepository")
 */
class SweepstakesEntry
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SweepstakesBundle\Entity\Sweepstakes", inversedBy="entries")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $sweepstakes;

    /**
     * @ORM\Column(name="ipAddress", type="string", length=255)
     */
    private $ipAddress;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesAnswer", mappedBy="entry", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $answers;

    public function __construct(Sweepstakes $sweepstakes)
    {
        $this->sweepstakes = $sweepstakes;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIpAddress($value)
    {
        $this->ipAddress = $value;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($value)
    {
        $this->user = $value;
    }

    public function getSweepstakes()
    {
        return $this->sweepstakes;
    }

    public function setSweepstakes($value)
    {
        $this->sweepstakes = $value;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($value)
    {
        $this->created = $value;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated($value)
    {
        $this->updated = $value;
    }

    public function getAnswers()
    {
        return $this->answers;
    }

    public function setAnswers($value)
    {
        $this->answers = $value;
    }
}
