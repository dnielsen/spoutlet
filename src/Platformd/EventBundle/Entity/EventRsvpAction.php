<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use DateTime,
    DateTimeZone
;

/**
 * Base EventRsvpAction
 *
 * @ORM\MappedSuperclass
 */
abstract class EventRsvpAction
{
    const ATTENDING_YES     = 'ATTENDING_YES';
    const ATTENDING_NO      = 'ATTENDING_NO';
    const ATTENDING_MAYBE   = 'ATTENDING_MAYBE';

    /**
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $user;

    /**
     * @ORM\Column(name="rsvp_at", type="datetime", nullable=true)
     *
     */
    protected $rsvpAt = null;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="string")
     *
     */
    protected $attendance;

    /**
     * @return Platformd\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Platformd\UserBundle\Entity\User
     */
    public function setUser($value)
    {
        $this->user = $value;
    }

    /**
     * @return \DateTime
     */
    public function getRsvpAt()
    {
        return $this->rsvpAt;
    }

    /**
     * @param \DateTime
     */
    public function setRsvpAt($value)
    {
        $this->rsvpAt = $value;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime
     */
    public function setUpdatedAt($value)
    {
        $this->updatedAt = $value;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime
     */
    public function setCreatedAt($value)
    {
        $this->createdAt = $value;
    }

    /**
     * @return string
     */
    public function getAttendance()
    {
        return $this->attendance;
    }

    /**
     * @param string
     */
    public function setAttendance($value)
    {
        $this->attendance = $value;
    }
}
