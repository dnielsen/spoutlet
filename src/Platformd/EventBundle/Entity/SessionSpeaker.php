<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 2/13/14
 * Time: 11:21 AM
 */

namespace Platformd\EventBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\EventBundle\Entity\SessionSpeaker
 *
 * @ORM\Table(name="session_speakers")
 * @ORM\Entity
 */
class SessionSpeaker
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\EventSession", inversedBy="speakers")
     */
    protected $session;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $speaker;

    /**
     * Speaker Bio: Short introduction to audiance.
     *
     * @ORM\Column(name="biography", type="text", nullable=true)
     */
    protected $biography;

    /**
     * Speaker Role within session: Possilble values moderator, host, special guest, etc 
     *
     * @ORM\Column(name="role", type="text", nullable=true)
     */
    protected $role;

    /**
     * Constructor
     */
    public function __construct(GroupEvent $event) {
        // $this->event        = $event;
        // $this->attendees    = new ArrayCollection();
        // $this->followers    = new ArrayCollection();
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
     * Set biography
     *
     * @param text $biography
     */
    public function setBiography($biography)
    {
        $this->biography = $biography;
    }

    /**
     * Get biography
     *
     * @return text 
     */
    public function getBiography()
    {
        return $this->biography;
    }

    /**
     * Set role
     *
     * @param text $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * Get role
     *
     * @return text 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set session
     *
     * @param Platformd\EventBundle\Entity\EventSession $session
     */
    public function setSession(\Platformd\EventBundle\Entity\EventSession $session)
    {
        $this->session = $session;
    }

    /**
     * Get session
     *
     * @return Platformd\EventBundle\Entity\EventSession 
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set speaker
     *
     * @param Platformd\UserBundle\Entity\User $speaker
     */
    public function setSpeaker(\Platformd\UserBundle\Entity\User $speaker)
    {
        $this->speaker = $speaker;
    }

    /**
     * Get speaker
     *
     * @return Platformd\UserBundle\Entity\User 
     */
    public function getSpeaker()
    {
        return $this->speaker;
    }
}