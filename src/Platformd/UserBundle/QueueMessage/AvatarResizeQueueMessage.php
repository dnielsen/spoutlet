<?php

namespace Platformd\UserBundle\QueueMessage;

use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;

class AvatarResizeQueueMessage extends SqsMessageBase
{
    public $userUuid;
    public $fileUuid;
    public $extension;
    public $width;
    public $height;
    public $x;
    public $y;

    public function __toString() {
        return 'AvatarResizeQueueMessage => { User = "'.$this->userUuid.'", File UUID = "'.$this->fileUuid.'", Extension = "'.$this->extension.'" }';
    }

    public function getQueueName() {
        return 'AVATAR_RESIZE';
    }
}
