<?php

namespace Platformd\UserBundle\QueueMessage;

use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;

class AvatarFileSystemActionsQueueMessage extends SqsMessageBase
{
    const AVATAR_FILESYSTEM_ACTION_APPROVE  = 'approve';
    const AVATAR_FILESYSTEM_ACTION_SWITCH   = 'switch';

    private static $validActions = array(
        self::AVATAR_FILESYSTEM_ACTION_APPROVE,
        self::AVATAR_FILESYSTEM_ACTION_SWITCH,
    );

    public $action;
    public $userUuid;
    public $fileUuid;

    public function __toString() {
        return 'AvatarResizeQueueMessage => { Action = "'.$this->action.'", User = "'.$this->userUuid.'", UUID = '.$this->fileUuid.'" }';
    }

    public function getQueueName() {
        return 'AVATAR_FILESYSTEM_ACTIONS';
    }

    public static function isValidAction($action)
    {
        return in_array($action, self::$validActions);
    }
}
