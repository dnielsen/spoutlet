<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Model\CommentableInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use DateTimezone;
use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;

/**
 * Platformd\GiveawayBundle\Entity\QueueStatus
 * @ORM\Table(name="pd_queue_status")
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\QueueStatusRepository")
 */

class QueueStatus
{
    const QUEUE_TYPE_GIVEAWAY = 'giveaway';
    const QUEUE_TYPE_DEAL     = 'deal';

    const REASON_NO_KEYS_LEFT = 'no-keys';

    private static $validStatuses = array(
        self::QUEUE_TYPE_GIVEAWAY,
        self::QUEUE_TYPE_DEAL
    );

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $type = self::QUEUE_TYPE_GIVEAWAY;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\Giveaway")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $giveaway;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $user;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $reason;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function setStatus($status)
    {
        if ($status && !in_array($status, self::$validStatuses)) {
            throw new \InvalidArgumentException(sprintf('Invalid status passed: "%s"', $status));
        }

        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
