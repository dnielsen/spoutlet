<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Gedmo\Mapping\Annotation as Gedmo;

use FOS\UserBundle\Model\UserInterface;

/**
 * Platformd\SweepstakesBundle\Entity\SweepstakesEntry
 *
 * @ORM\Table(name="pd_sweepstakes_entry")
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepstakesEntryRepository")
 *
 * @UniqueEntity(fields={"user", "sweepstakes"}, message="sweepstakes.errors.entry_unique")
 */
class SweepstakesEntry
{
    /**
     * @Assert\True(message="sweepstakes.errors.agree_to_terms")
     */
    public $termsAccepted;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
     * @ORM\Column(name="ip_address", type="string", length=40)
     */
    protected $ipAddress;

    /**
     * @ORM\Column(name="phone_number", type="string", length=50)
     * @Assert\NotBlank()
     */
    protected $phoneNumber;

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
    protected $answers;

    public function __construct(Sweepstakes $sweepstakes)
    {
        $this->sweepstakes = $sweepstakes;
        $this->answers     = new ArrayCollection();
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

    public function setPhoneNumber($value)
    {
        $this->phoneNumber = $value;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
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

    public function addAnswer($answer)
    {
        $this->answers->add($answer);
    }
}
