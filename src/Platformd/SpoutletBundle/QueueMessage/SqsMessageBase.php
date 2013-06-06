<?php

namespace Platformd\SpoutletBundle\QueueMessage;

abstract class SqsMessageBase {

    private $sqsMessageId;
    private $sqsReceiptHandle;

    abstract public function getQueueName();
    abstract public function __toString();

    public function setSqsMessageId($value) {
        $this->sqsMessageId = $value;
    }

    public function getSqsMessageId() {
        return $this->sqsMessageId;
    }

    public function setSqsReceiptHandle($value) {
        $this->sqsReceiptHandle = $value;
    }

    public function getSqsReceiptHandle() {
        return $this->sqsReceiptHandle;
    }
}
