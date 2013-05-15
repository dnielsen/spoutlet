<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="rsvp_code")
 **/
class RsvpCode
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    protected $value;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Rsvp", inversedBy="codes")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $rsvp;

    public function __toString()
    {
        return (string) $this->value;
    }

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getRsvp()
    {
        return $this->rsvp;
    }

    public function setRsvp($rsvp)
    {
        $this->rsvp = $rsvp;
    }
}

