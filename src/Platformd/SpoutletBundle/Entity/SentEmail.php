<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\SentEmail
 *
 * @ORM\Table(name="pd_sent_emails")
 * @ORM\Entity()
 */
class SentEmail
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotNull
     */
    private $recipient;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotNull
     */
    private $fromFull;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotNull
     */
    private $subject;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotNull
     */
    private $body;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sesMessageId;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\NotNull
     */
    private $sendStatusOk;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotNull
     */
    private $sendStatusCode;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotNull
     */
    private $siteEmailSentFrom;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotNull
     */
    private $emailType;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    public function getId()
    {
        return $this->id;
    }

    public function setRecipient($value)
    {
        $this->recipient = $value;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setFromFull($value)
    {
        $this->fromFull = $value;
    }

    public function getFromFull()
    {
        return $this->fromFull;
    }

    public function setSubject($value)
    {
        $this->subject = $value;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setBody($value)
    {
        $this->body = $value;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setSesMessageId($value)
    {
        $this->sesMessageId = $value;
    }

    public function getSesMessageId()
    {
        return $this->sesMessageId;
    }

    public function setSendStatusOk($value)
    {
        $this->sendStatusOk = $value;
    }

    public function getSendStatusOk()
    {
        return $this->sendStatusOk;
    }

    public function setSendStatusCode($value)
    {
        $this->sendStatusCode = $value;
    }

    public function getSendStatusCode()
    {
        return $this->sendStatusCode;
    }

    public function setSiteEmailSentFrom($value)
    {
        $this->siteEmailSentFrom = $value;
    }

    public function getSiteEmailSentFrom()
    {
        return $this->siteEmailSentFrom;
    }

    public function setEmailType($value)
    {
        $this->emailType = $value;
    }

    public function getEmailType()
    {
        return $this->emailType;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
