<?php

namespace Platformd\SpoutletBundle\Util\Interfaces;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Platformd\SpoutletBundle\Exception\QueueFailureException;
use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;

interface QueueUtilInterface
{
    public function __construct($sqsClient, $logger, $queueUrlPrefix, $mockWorkingFile);
    public function addToQueue(SqsMessageBase $message);
    public function deleteFromQueue(SqsMessageBase $message);
    public function retrieveFromQueue(SqsMessageBase $message);
}
