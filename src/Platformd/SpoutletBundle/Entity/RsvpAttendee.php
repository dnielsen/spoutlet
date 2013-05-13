<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\SpoutletBundle\Validator\RsvpAttendee as RsvpCodeValidator;
use Symfony\Component\Validator\ExecutionContext;
use Platformd\SpoutletBundle\Entity\RsvpCode;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\RsvpAttendeeRepository")
 * @ORM\Table(name="rsvp_attendee")
 * @UniqueEntity(fields={"email", "rsvp"}, message="This email address is already registered.")
 * @UniqueEntity(fields={"phoneNumber", "rsvp"}, message="This phone number is already registered.")
 * @RsvpCodeValidator()
 **/
class RsvpAttendee
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull(message="Required")
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull(message="Required")
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull(message="Required")
     * @Assert\Email
     */
    protected $email;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull(message="Required")
     */
    protected $phoneNumber;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Rsvp", inversedBy="attendees")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $rsvp;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\RsvpCode", inversedBy="attendee")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $code;

    public function __construct(Rsvp $rsvp = null)
    {
        $this->rsvp = $rsvp;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getRsvp()
    {
        return $this->rsvp;
    }

    public function setRsvp($rsvp)
    {
        $this->rsvp = $rsvp;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }
}

