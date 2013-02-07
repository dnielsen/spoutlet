<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

use Gedmo\Mapping\Annotation as Gedmo;

use Platformd\UserBundle\Entity\User;

/**
 * Base EventEMail
 *
 * @ORM\MappedSuperclass
 */
abstract class EventEmail
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
     * Email subject
     *
     * @var string $name
     * @Assert\NotBlank()
     * @ORM\Column(name="subject", type="string", length=255)
     */
    protected $subject;

    /**
     * Email message body
     *
     * @var string $message
     * @Assert\NotBlank()
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    protected $message;

    /**
     * Email recipients
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $recipients;

    /**
     * @var \DateTime $sentAt
     *
     * @ORM\Column(name="sent_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $sentAt;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
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
     * Set subject
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set message
     *
     * @param text $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get message
     *
     * @return text
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

     /**
     * @param \Doctrine\Common\Collections\ArrayCollection $recipients
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;
    }

    /**
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * @param \DateTime $sentAt
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;
    }
}
