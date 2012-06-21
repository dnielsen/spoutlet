<?php

namespace Platformd\SpoutletBundle\Entity\Superclass;

use Doctrine\ORM\Mapping as ORM;

use Platformd\UserBundle\Entity\User;
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
abstract class Code
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $value
     *
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
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     */
    abstract public function setPool(Pool $pool);

    /**
     * @return \Platformd\SpoutletBundle\Entity\Superclass\Pool
     */
    abstract public function getPool();

    /**
     * @param User $user
     */
    abstract public function setUser(User $user);

    /**
     * @return User
     */
    abstract public function getUser();

    public function __construct($value)
    {
        $this->setValue($value);
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
     * Set value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function assign(User $user, $ipAddress, $site)
    {
        $this->setUser($user);
        $this->assignedAt = new \DateTime();
        $this->ipAddress = $ipAddress;
        $this->setAssignedSite($site);
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
}