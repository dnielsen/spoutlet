<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Platformd\UserBundle\Entity\User;
use Platformd\GiveawayBundle\Entity\DealPool;
use Platformd\SpoutletBundle\Entity\AbstractEvent;

/**
 * Represents a code/key that's given away
 *
 * To sub-class this, you must add the "pool" and "user" properties and
 * mappings (+ the 4 abstract methods), since this cannot be done from the
 * mapped superclass.
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractCode
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="value", type="string", length=255)
     */
    protected $value;

    /**
     * @ORM\Column(name="assigned_at", type="datetime", nullable=true)
     */
    protected $assignedAt;

    /**
     * Holds the site/locale that this key was assigned under
     *
     * @ORM\Column(name="assigned_site", type="string", nullable=true, length="10")
     */
    protected $assignedSite;

    /**
     * @ORM\Column(name="ip_address", type="string", nullable=true, length=100)
     */
    protected $ipAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Country")
     */
    protected $country;

    abstract public function setPool(AbstractPool $pool);
    abstract public function getPool();
    abstract public function setUser(User $user);
    abstract public function getUser();

    public function __construct($value)
    {
        $this->setValue($value);
    }

    public function __toString() {
        return 'Key => { Id = '.$this->getId().', Value = "'.$this->getProtectedValue().'" }';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getProtectedValue() {
        if (!$this->value || !is_string($this->value)) {
            return 'BLANK';
        }

        $length = strlen($this->value);

        if ($length < 8) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($this->value,-4,4);
    }

    public function assign(User $user, $ipAddress, $site, $country)
    {
        $this->setUser($user);
        $this->assignedAt = new \DateTime();
        $this->ipAddress = $ipAddress;
        $this->setAssignedSite($site);
        $this->setCountry($country);
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function getAssignedAt()
    {
        return $this->assignedAt;
    }

    public function getAssignedAtTranslationArray()
    {
        return AbstractEvent::convertDateTimeIntoTranslationArray($this->getAssignedAt());
    }

    public function getAssignedSite()
    {
        return $this->assignedSite;
    }

    public function setAssignedSite($assignedSite)
    {
        $this->assignedSite = $assignedSite;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($value)
    {
        $this->country = $value;
    }
}
