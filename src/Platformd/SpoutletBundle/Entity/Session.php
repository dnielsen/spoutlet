<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Platformd\SpoutletBundle\Entity\Session
 *
 * @ORM\Table(name="session")
 * @ORM\Entity
 */
class Session
{
    /**
     * @var string $session_id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(name="session_id", type="string", length=255)
     */
    private $session_id;

    /**
     * @var text $session_value
     *
     * @ORM\Column(name="session_value", type="text")
     */
    private $session_value;

    /**
     * @var integer $session_time
     *
     * @ORM\Column(name="session_time", type="integer")
     */
    private $session_time;


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
     * Set session_id
     *
     * @param string $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;
    }

    /**
     * Get session_id
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set session_value
     *
     * @param text $sessionValue
     */
    public function setSessionValue($sessionValue)
    {
        $this->session_value = $sessionValue;
    }

    /**
     * Get session_value
     *
     * @return text
     */
    public function getSessionValue()
    {
        return $this->session_value;
    }

    /**
     * Set session_time
     *
     * @param integer $sessionTime
     */
    public function setSessionTime($sessionTime)
    {
        $this->session_time = $sessionTime;
    }

    /**
     * Get session_time
     *
     * @return integer
     */
    public function getSessionTime()
    {
        return $this->session_time;
    }
}
