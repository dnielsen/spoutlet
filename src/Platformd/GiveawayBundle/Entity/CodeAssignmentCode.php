<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Platformd\GiveawayBundle\Entity\CodeAssignmentCode
 *
 * @ORM\Table(name="code_assignment_code")
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\CodeAssignmentCodeRepository")
 */
class CodeAssignmentCode
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="codeAssignmentCodes", cascade={"persist", "remove", "merge"})
     */
    protected $user;

    /**
     * @ORM\JoinColumn(name="assignment", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\CodeAssignment", inversedBy="codes", cascade={"persist", "remove", "merge"})
     */
    protected $assignment;

    /**
     * @ORM\Column(name="code", type="string", length="255")
     */
    private $code;

    /**
     * @ORM\Column(name="email_sent_at", type="datetime", nullable="true")
     */
    protected $emailSentAt;

    public function __construct($user, $code) {
        $this->user = $user;
        $this->code = $code;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($value)
    {
        $this->user = $value;
    }

    public function getAssignment()
    {
        return $this->assignment;
    }

    public function setAssignment($value)
    {
        $this->assignment = $value;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($value)
    {
        $this->code = $value;
    }

    public function getEmailSentAt()
    {
        return $this->emailSentAt;
    }

    public function setEmailSentAt($value)
    {
        $this->emailSentAt = $value;
    }
}
