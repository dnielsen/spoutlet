<?php

namespace Platformd\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

/**
 * @ORM\Entity(repositoryClass="Platformd\UserBundle\Entity\SuspendedIpAddressRepository")
 * @ORM\Table(name="suspended_ip_address",indexes={@ORM\index(name="ip_date_idx", columns={"ip_address", "suspended_until"})})
 */
class SuspendedIpAddress
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="ip_address", type="string", length=50)
     */
    private $ipAddress;

    /**
     * @ORM\Column(name="suspended_until", type="datetime", nullable=true)
     */
    protected $suspendedUntil;

    public function __construct($ipAddress, DateTime $suspendedUntil = null) {
        $this->ipAddress      = $ipAddress;
        $this->suspendedUntil = $suspendedUntil;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress($value)
    {
        $this->ipAddress = $value;
    }

    public function getSuspendedUntil()
    {
        return $this->suspendedUntil;
    }

    public function setSuspendedUntil($value)
    {
        $this->suspendedUntil = $value;
    }
}
