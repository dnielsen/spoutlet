<?php

namespace Platformd\SpoutletBundle\Util\Mocks;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Platformd\SpoutletBundle\Exception\QueueFailureException;
use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;
use Platformd\SpoutletBundle\Util\Interfaces\QueueUtilInterface;
use Platformd\SpoutletBundle\QueueMessage\KeyRequestQueueMessage;

class QueueUtilMock implements QueueUtilInterface
{
    private $mockWorkingFile;

    public function __construct($sqsClient, $logger, $queueUrlPrefix, $mockWorkingFile)
    {
        $this->mockWorkingFile = $mockWorkingFile;
    }

    public function emptyWorkingFile() {
        fclose(fopen($this->mockWorkingFile, 'w'));
    }

    private function getLine($message) {
        return serialize($message)."\n";
    }

    public function addToQueue(SqsMessageBase $message) {
        file_put_contents($this->mockWorkingFile, $this->getLine($message), FILE_APPEND | LOCK_EX);
        return true;
    }

    public function deleteFromQueue(SqsMessageBase $message) {

        $line      = $this->getLine($message);
        $fileLines = file($this->mockWorkingFile);

        for ($i = count($fileLines) - 1; $i >= 0; $i--) {
            if ($line === $fileLines[$i]) {
                unset($fileLines[$i]);
            }
        }

        $output = implode($fileLines);

        file_put_contents($this->mockWorkingFile, $output, LOCK_EX);

        return true;
    }

    public function retrieveFromQueue(SqsMessageBase $message) {

        $fileLines = file($this->mockWorkingFile);

        if (!isset($fileLines[0])) {
            return null;
        }

        $instanceOfMessage = get_class($message);

        for ($i = count($fileLines) - 1; $i >= 0; $i--) {

            $storedMessage = unserialize($fileLines[$i]);

            if ($storedMessage instanceof $instanceOfMessage) {
                return $storedMessage;
            }
        }

        return null;
    }
}
