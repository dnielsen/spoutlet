<?php

namespace Platformd\GiveawayBundle\Model;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\GiveawayBundle\Entity\MachineCodeEntry;

/**
 * Generic "model" that represents a giveaway key or request for a giveaway key made for a user
 *
 * This is the type of object that shows up on the user's giveaway keys page.
 * The source of this could either be an actual GiveawayKey OR a MachineCodeEntry
 * that is still pending or denied.
 */
class GiveawayKeyRequest
{
    private $value;

    private $giveaway;

    private $assignedAt;

    /**
     * A MachineCodeEntry status
     *
     * @var string
     */
    private $status;

    public function __construct($value, Giveaway $giveaway, $status, \DateTime $assignedAt = null)
    {
        $this->value = $value;
        $this->giveaway = $giveaway;
        $this->status = $status;
        $this->assignedAt = $assignedAt;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getGiveaway()
    {
        return $this->giveaway;
    }

    public function getAssignedAt()
    {
        return $this->assignedAt;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getAssignedAtTranslationArray()
    {
        return AbstractEvent::convertDateTimeIntoTranslationArray($this->getAssignedAt());
    }
}