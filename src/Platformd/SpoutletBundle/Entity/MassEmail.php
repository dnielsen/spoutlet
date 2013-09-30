<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

use Gedmo\Mapping\Annotation as Gedmo;

use Platformd\UserBundle\Entity\User;

/**
 * Base MassEMail
 *
 * @ORM\MappedSuperclass
 */
abstract class MassEmail
{
    const EMAIL_LIMIT_COUNT  = 2;
    const EMAIL_LIMIT_PERIOD = '24 hours';

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
     * @Assert\NotBlank(message="(required field)")
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
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    protected $sentAt;

    /**
     * Email sender
     *
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $sender;

    /**
     * Site email is sent from
     *
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $site;

    /**
     * @ORM\Column(name="sent_to_all", type="boolean")
     */
    protected $sentToAll;

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

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return User
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param User
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param Site
     */
    public function setSite($site)
    {
        $this->site = $site;
    }

    /**
     * @return boolean
     */
    public function getSentToAll()
    {
        return $this->sentToAll;
    }

    /**
     * @param boolean $value
     */
    public function setSentToAll($value)
    {
        $this->sentToAll = $value;
    }

    public function getEmailType()
    {
        return 'Mass Email';
    }

    abstract protected function getLinkedEntityClass();
    abstract protected function getLinkedEntity();
    abstract protected function getLinkedEntityAllRecipientsField();
}
