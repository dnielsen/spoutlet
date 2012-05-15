<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\UserBundle\Entity\User;

/**
 * Represents a machine code entry for a giveaway
 *
 * @ORM\Table(name="pd_machine_code_entry")
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\MachineCodeEntryRepository")
 */
class MachineCodeEntry
{
    const STATUS_PENDING    = 'pending';
    const STATUS_APPROVED   = 'approved';
    const STATUS_DENIED     = 'denied';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $machineCode
     *
     * @ORM\Column(name="machineCode", type="string", length=255)
     */
    private $machineCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ipAddress;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    protected $status = self::STATUS_PENDING;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $approvedAt;

    /**
     * If an email was sent regarding the approval of this entry, when was it sent?
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $notificationEmailSentAt;

    /**
     * The user assigned to this key
     *
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $user;

    /**
     * The related giveaway
     *
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="Giveaway")
     */
    protected $giveaway;

    /**
     * An optional related GiveawayKey
     *
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="GiveawayKey")
     */
    protected $key;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    public function __construct(Giveaway $giveaway, $code)
    {
        $this->giveaway = $giveaway;
        $this->machineCode = $code;
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
     * Call this to attach this entry to a specific user on creation
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @param $ipAddress
     */
    public function attachToUser(User $user, $ipAddress)
    {
        $this->user = $user;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Call this to attach this to a GiveawayKey
     *
     * @param GiveawayKey $key
     */
    public function attachToKey(GiveawayKey $key)
    {
        $this->key = $key;
        $this->status = self::STATUS_APPROVED;
        $this->setApprovedAt(new \DateTime());
    }

    /**
     * Get machineCode
     *
     * @return string 
     */
    public function getMachineCode()
    {
        return $this->machineCode;
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getGiveaway()
    {
        return $this->giveaway;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getApprovedAt()
    {
        return $this->approvedAt;
    }

    /**
     * @param \DateTime $approvedAt
     */
    public function setApprovedAt($approvedAt)
    {
        $this->approvedAt = $approvedAt;
    }

    /**
     * @return \DateTime
     */
    public function getNotificationEmailSentAt()
    {
        return $this->notificationEmailSentAt;
    }

    /**
     * @param \DateTime $notificationEmailSentAt
     */
    public function setNotificationEmailSentAt($notificationEmailSentAt)
    {
        $this->notificationEmailSentAt = $notificationEmailSentAt;
    }
}