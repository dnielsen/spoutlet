<?php

namespace Platformd\SpoutletBundle\QueueMessage;

use Platformd\SpoutletBundle\QueueMessage\MassEmailQueueMessage;

class ChunkedMassEmailQueueMessage extends MassEmailQueueMessage
{
    const RECIPIENT_CHUNK_SIZE = 100;

    public $recipientIds = array();

    public function __toString() {
        return 'ChunkedMassEmailQueueMessage => { EmailType = "'.$this->emailType.'", EmailId = '.$this->emailId.', Recipients = '.count($this->recipientIds).' }';
    }

    public function getQueueName() {
        return 'CHUNKED_MASS_EMAIL';
    }
}
