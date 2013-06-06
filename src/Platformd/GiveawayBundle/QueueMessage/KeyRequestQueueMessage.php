<?php

namespace Platformd\GiveawayBundle\QueueMessage;

use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;

class KeyRequestQueueMessage extends SqsMessageBase
{
    const KEY_REQUEST_TYPE_DEAL     = 'deal';
    const KEY_REQUEST_TYPE_GIVEAWAY = 'giveaway';
    public static $allowedTypes     = array(self::KEY_REQUEST_TYPE_GIVEAWAY, self::KEY_REQUEST_TYPE_DEAL);

    public $keyRequestType;
    public $promotionId;
    public $dateTime;
    public $slug;
    public $userId;
    public $siteId;
    public $ipAddress;

    public function __toString() {
        return 'KeyRequestQueueMessage => { DateTime = "'.($this->dateTime instanceof \DateTime ? $this->dateTime->format('Y-m-d H:i:s') : 'UNKNOWN').'", PromotionId = '.$this->promotionId.', KeyRequestType = "'.$this->keyRequestType.'", UserId = '.$this->userId.', SiteId = '.$this->siteId.', IpAddress = "'.$this->ipAddress.'", Slug = "'.$this->slug.'" }';
    }

    public function getQueueName() {
        return 'KEY_REQUEST';
    }

    public function hasValidKeyRequestType() {
        return self::isAllowedKeyRequestType($this->keyRequestType);
    }

    public static function isAllowedKeyRequestType($value) {
        if (!$value) {
            return false;
        }

        return in_array($value, self::$allowedTypes, true);
    }
}
