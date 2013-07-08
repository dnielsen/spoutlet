<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Model\CommentableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use DateTimezone;
use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;

/**
 * Platformd\GiveawayBundle\Entity\KeyRequestState
 * @ORM\Table(name="pd_key_request_state")
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\Repository\KeyRequestStateRepository")
 */

class KeyRequestState
{
    const PROMOTION_TYPE_GIVEAWAY = 'giveaway';
    const PROMOTION_TYPE_DEAL     = 'deal';

    const STATE_IN_QUEUE            = 'in_queue';
    const STATE_REJECTED            = 'rejected';
    const STATE_ASSIGNED            = 'assigned';
    const STATE_NONE                = 'none';
    const STATE_REQUEST_PROBLEM     = 'problem_with_request';

    const REASON_NONE                   = 'none';
    const REASON_NO_KEYS_LEFT           = 'no_keys';
    const REASON_ALREADY_ASSIGNED       = 'already_assigned';
    const REASON_INVALID_COUNTRY_AGE    = 'invalid_country_or_age';
    const REASON_IP_REACHED_MAX         = 'ip_reached_max';
    const REASON_INVALID_SITE           = 'invalid_site';
    const REASON_CLIENT_IP_NULL         = 'client_ip_null';
    const REASON_COUNTRY_CODE_NULL      = 'country_code_null';
    const REASON_INVALID_COUNTRY        = 'invalid_country';
    const REASON_INVALID_PROMOTION      = 'invalid_promotion';
    const REASON_INACTIVE_PROMOTION     = 'inactive_promotion';
    const REASON_KEY_FETCH_DISALLOWED   = 'key_fetch_disallowed';

    private static $validPromotionTypes = array(
        self::PROMOTION_TYPE_GIVEAWAY,
        self::PROMOTION_TYPE_DEAL,
    );

    private static $validStates = array(
        self::STATE_IN_QUEUE,
        self::STATE_ASSIGNED,
        self::STATE_NONE,
        self::STATE_REJECTED,
        self::STATE_REQUEST_PROBLEM,
    );

    private static $validReasons = array(
        self::REASON_NONE,
        self::REASON_NO_KEYS_LEFT,
        self::REASON_ALREADY_ASSIGNED,
        self::REASON_INVALID_COUNTRY_AGE,
        self::REASON_INVALID_SITE,
        self::REASON_CLIENT_IP_NULL,
        self::REASON_IP_REACHED_MAX,
        self::REASON_COUNTRY_CODE_NULL,
        self::REASON_INVALID_COUNTRY,
        self::REASON_INVALID_PROMOTION,
        self::REASON_INACTIVE_PROMOTION,
        self::REASON_KEY_FETCH_DISALLOWED,
    );

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="promotion_type", type="string", length=50, nullable=false)
     */
    private $promotionType;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\Giveaway")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $giveaway;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\Deal")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $deal;

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
     * Updated At
     *
     * @ORM\Column(type="datetime", name="updated_at")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $currentState;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $stateReason;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $userHasSeenState = false;

    public function getId()
    {
        return $this->id;
    }

    public function getGiveaway() {
        return $this->giveaway;
    }

    public function setGiveaway($value) {
        $this->giveaway = $value;
    }

    public function getUserHasSeenState() {
        return $this->userHasSeenState;
    }

    public function setUserHasSeenState($value) {
        $this->userHasSeenState = $value;
    }

    public function getDeal() {
        return $this->deal;
    }

    public function setDeal($value) {
        $this->deal = $value;
    }

    public function getUser() {
        return $this->user;
    }

    public function setUser($value) {
        $this->user = $value;
    }

    public function setCurrentState($value)
    {
        if ($value && !in_array($value, self::$validStates)) {
            throw new \InvalidArgumentException(sprintf('Invalid state passed: "%s"', $value));
        }

        $this->currentState = $value;
    }

    public function getCurrentState()
    {
        return $this->currentState;
    }

    public function setPromotionType($value)
    {
        if ($value && !in_array($value, self::$validPromotionTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid promotion type passed: "%s"', $value));
        }

        $this->promotionType = $value;
    }

    public function getPromotionType()
    {
        return $this->promotionType;
    }

    public function setStateReason($value)
    {
        if ($value && !in_array($value, self::$validReasons)) {
            throw new \InvalidArgumentException(sprintf('Invalid state reason passed: "%s"', $value));
        }

        $this->stateReason = $value;
    }

    public function getStateReason()
    {
        return $this->stateReason;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($value)
    {
        $this->createdAt = $value;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($value)
    {
        $this->updatedAt = $value;
    }

    public function __toString()
    {
        return 'KeyRequestState => { State = '.$this->getCurrentState().', Reason = "'.$this->getStateReason().'" }';
    }
}
