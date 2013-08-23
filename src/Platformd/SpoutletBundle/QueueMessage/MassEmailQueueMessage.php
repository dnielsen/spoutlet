<?php

namespace Platformd\SpoutletBundle\QueueMessage;

use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;

class MassEmailQueueMessage extends SqsMessageBase
{
    const EMAIL_TYPE_GROUP        = 'Group Mass Email';
    const EMAIL_TYPE_GLOBAL_EVENT = 'Global Event Mass Email';
    const EMAIL_TYPE_GROUP_EVENT  = 'Group Event Mass Email';

    public static $validTypes = array(self::EMAIL_TYPE_GROUP, self::EMAIL_TYPE_GLOBAL_EVENT, self::EMAIL_TYPE_GROUP_EVENT);

    public static $typeClassMap = array(
      self::EMAIL_TYPE_GROUP        => 'GroupBundle:GroupMassEmail',
      self::EMAIL_TYPE_GLOBAL_EVENT => 'EventBundle:GlobalEventEmail',
      self::EMAIL_TYPE_GROUP_EVENT  => 'EventBundle:GroupEventEmail',
    );

    public $senderId;
    public $emailType;
    public $emailId;

    public function __toString() {
        return 'MassEmailQueueMessage => { EmailType = "'.$this->emailType.'", EmailId = '.$this->emailId.', SenderId = '.$this->senderId.' }';
    }

    public function getQueueName() {
        return 'MASS_EMAIL';
    }

    public function hasValidEmailType() {
        return self::isAllowedEmailType($this->emailType);
    }

    public static function isAllowedEmailType($value) {
        if (!$value) {
            return false;
        }

        return in_array($value, self::$validTypes, true);
    }
}
