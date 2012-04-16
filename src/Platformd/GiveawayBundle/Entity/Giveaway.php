<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Gedmo\Mapping\Annotation as Gedmo;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\GiveawayBundle\Entity\Giveaway
 *
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\GiveawayRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Giveaway extends AbstractEvent
{
    // the traditional key giveaway type
    const TYPE_KEY_GIVEAWAY = 'key_giveaway';

    // the machine-submit giveaway type
    const TYPE_MACHINE_CODE_SUBMIT = 'machine_code_submit';

    const TYPE_TEXT_PREFIX = 'giveaway.type.';

    /**
     * One to Many with GiveawayPool
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayPool", mappedBy="giveaway")
     */
    protected $giveawayPools;

    /**
     * This is a raw HTML field, but with a special format.
     *
     * Each line will be exploded into an array, and used for numbered
     * instructions on the giveaway.
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     *
     * @var string
     */
    protected $redemptionInstructions;

    /**
     * A string enum status
     *
     * @var string
     * @ORM\Column(type="string", length=15)
     */
    protected $status = 'disabled';

    /**
     * @var string
     * @ORM\Column(type="string", length=30)
     */
    protected $giveawayType = self::TYPE_KEY_GIVEAWAY;

    /**
     * Key of valid status to a text translation key for that status
     *
     * @var array
     */
    static protected $validStatuses = array(
        // totally disabled
        'disabled' => 'platformd.giveaway.status.disabled',
        // active but with zero keys
        'inactive' => 'platformd.giveaway.status.inactive',
        // totally awesome active
        'active' => 'platformd.giveaway.status.active',
    );

    public function __construct()
    {
        // auto-publish, this uses the "status" field instead
        $this->published = true;
        $this->giveawayPools = new ArrayCollection();
    }

    public function __toString()
    {
        
        return $this->getName();
    }
    
    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGiveawayPools()
    {
        return $this->giveawayPools;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $giveawayPools
     */
    public function setGiveawayPools($giveawayPools)
    {
        $this->giveawayPools = $giveawayPools;
    }

    /**
     * Add an user
     *
     * @param \Platformd\UserBundle\Entity\GiveawayPool $pool
     */
    public function addUser(GiveawayPool $pool)
    {
        $this->giveawayPools->add($pool);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        if (!$status) {
            return;
        }

        if (!in_array($status, array_keys(self::$validStatuses))) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s" given', $status));
        }

        $this->status = $status;
    }

    /**
     * Returns the "text" for the current status
     *
     * The text is actually just a translation key
     *
     * @return string
     */
    public function getStatusText()
    {
        return self::$validStatuses[$this->getStatus() ?: 'disabled'];
    }

    /**
     * Returns a key-value pair of valid status keys and their text translation
     *
     * Useful in forms
     *
     * @return array
     */
    static public function getValidStatusesMap()
    {
        return self::$validStatuses;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->getStatus() == 'disabled';
    }

    public function isActive()
    {
        return $this->getStatus() == 'active';
    }

    /**
     * @return string
     */
    public function getRedemptionInstructions()
    {
        return $this->redemptionInstructions;
    }

    /**
     * @param string $redemptionInstructions
     */
    public function setRedemptionInstructions($redemptionInstructions)
    {
        $this->redemptionInstructions = $redemptionInstructions;
    }

    /**
     * Explodes the redemptionInstructions by new line into an array of instructions
     *
     * @return array
     */
    public function getRedemptionInstructionsArray()
    {
        return explode("\n", $this->getRedemptionInstructions());
    }

    /**
     * Allows you to set the redemption instructions where each step is
     * an item in an array
     *
     * @param array $instructions
     */
    public function setRedemptionInstructionsArray(array $instructions)
    {
        $this->setRedemptionInstructions(implode("\n", $instructions));
    }

    /**
     * Returns the "active" pool, which is just the first one we find that
     * is indeed active
     *
     * @return \Platformd\GiveawayBundle\Entity\GiveawayPool
     */
    public function getActivePool()
    {
        foreach($this->getGiveawayPools() as $pool) {
            if ($pool->getIsActive()) {
                return $pool;
            }
        }
    }

    /**
     * Makes sure the redemption instructions are trimmed
     *
     * @ORM\prePersist
     * @ORM\preUpdate
     */
    public function trimRedemptionInstructions()
    {
        $this->setRedemptionInstructions(trim($this->getRedemptionInstructions()));
    }

    /**
     * Returns the route name to this item's show page
     *
     * @return string
     */
    public function getShowRouteName()
    {
        return 'giveaway_show';
    }

    /**
     * @return string
     */
    public function getGiveawayType()
    {
        return $this->giveawayType;
    }

    public function giveawayTypeText()
    {
        return self::TYPE_TEXT_PREFIX.$this->getGiveawayType();
    }

    /**
     * @param string $giveawayType
     */
    public function setGiveawayType($giveawayType)
    {
        if ($giveawayType != self::TYPE_KEY_GIVEAWAY && $giveawayType != self::TYPE_MACHINE_CODE_SUBMIT) {
            throw new \InvalidArgumentException(sprintf('Invalid giveaway type "%s" given', $giveawayType));
        }

        $this->giveawayType = $giveawayType;
    }

    /**
     * @return bool
     */
    public function getShowKeys()
    {
        // show the keys if its a traditional key giveaway
        return $this->getGiveawayType() == self::TYPE_KEY_GIVEAWAY;
    }

    /**
     * Whether or not a user is able to freely register for giveaway keys for this giveaway
     *
     * @return bool
     */
    public function allowKeyFetch()
    {
        return self::TYPE_KEY_GIVEAWAY == $this->getGiveawayType();
    }

    static public function getTypeChoices()
    {
        return array(
            self::TYPE_KEY_GIVEAWAY => self::TYPE_TEXT_PREFIX.self::TYPE_KEY_GIVEAWAY,
            self::TYPE_MACHINE_CODE_SUBMIT => self::TYPE_TEXT_PREFIX.self::TYPE_MACHINE_CODE_SUBMIT,
        );
    }
}