<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Platformd\SpoutletBundle\Exception\QueueFailureException;
use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;
use Platformd\SpoutletBundle\Util\Interfaces\QueueUtilInterface;
use Platformd\SpoutletBundle\HPCloud\HPCloudPHP;
class QueueUtil implements QueueUtilInterface
{
    const LOG_MESSAGE_PREFIX = "[QueueUtil] ";

    private $logger;
    private $sqsClient;
    private $queueUrlPrefix;
    private $hpcloudObj;
    public function __construct($sqsClient, $logger, $queueUrlPrefix, $mockWorkingFile, $hpcloud_accesskey='', $hpcloud_secreatkey='', $hpcloud_tenantid='', $hpcloud_messaging_url='', $object_storage='')
    {
        $this->sqsClient      = $sqsClient;
        $this->logger         = $logger;
        $this->queueUrlPrefix = $queueUrlPrefix;
        if($object_storage == 'HpObjectStorage') {
          $this->object_storage = $object_storage;
          $this->hpCloudObj = new HPCloudPHP($hpcloud_accesskey,$hpcloud_secreatkey,$hpcloud_tenantid);
          $this->hpcloud_messaging_url = $hpcloud_messaging_url; 
        }
        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'queue prefix is "'.$queueUrlPrefix.'"');
    }

    private function criticalAbort($message) {
        $this->logger->crit(self::LOG_MESSAGE_PREFIX.$message);
        throw new QueueFailureException($message);
    }

    private function getFullQueueUrl($message) {
        $queueName = $message->getQueueName();

        $this->ensureValidQueueName($queueName);

        return $this->queueUrlPrefix.$queueName;
    }

    private function ensureValidQueueName($queueName) {

        list(, $caller) = debug_backtrace(false);
        $callerFunction = $caller['function'];

        if (!$queueName || strlen($queueName) < 3) {
            $this->criticalAbort($callerFunction.' - queueName was not valid.');
        }

        if (strlen($queueName) > 50) {
            $this->criticalAbort($callerFunction.' - queueName was too long... it must be 50 characters or less.');
        }
    }

    public function addToQueue(SqsMessageBase $message) {

        $fullQueueUrl = $this->getFullQueueUrl($message);

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'addToQueue - sending message to queue "'.$fullQueueUrl.'".');

        $messageBody = serialize($message);
        if($this->object_storage == 'HpObjectStorage') {
            $queueName = $message->getQueueName();
            $this->ensureValidQueueName($queueName);

            $result = $this->hpCloudObj->sendMessageToQueue($queueName,base64_encode($messageBody),$this->hpcoud_messaging_url);
        }
        else {
          $result  = $this->sqsClient->send_message($fullQueueUrl, base64_encode($messageBody));
        }

        if($this->object_storage == 'HpObjectStorage') {
           if($result == '') {
          $this->logger->err(self::LOG_MESSAGE_PREFIX.'addToQueue - could not send message to "'.$queueName);
            return false;
          }

        } else {
        if (!$result->isOK()) {
            $this->logger->err(self::LOG_MESSAGE_PREFIX.'addToQueue - could not send message to "'.$fullQueueUrl.'" because of error => "'.$result->body->Error->Message.'", while trying to send messageBody => "'.$messageBody.'.".');
            return false;
        }
        }

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'addToQueue - message successfully sent to queue "'.$fullQueueUrl.'".');

        return true;
    }

    public function deleteFromQueue(SqsMessageBase $message) {


        if($this->object_storage == 'HpObjectStorage') {
          $queueName = $message->getQueueName();
          $this->logger->debug(self::LOG_MESSAGE_PREFIX.'deleteFromHPQueue - deleting message "'.$message->hpQueueSqsId.'" from queue "'.$queueName.'".');
         

        } else {
        $fullQueueUrl = $this->getFullQueueUrl($message);

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'deleteFromQueue - deleting message "'.$message->amazonSqsId.'" from queue "'.$fullQueueUrl.'".');

        $result = $this->sqsClient->delete_message($fullQueueUrl, $message->amazonReceiptHandle);

        if (!$result->isOK()) {
            $this->logger->err(self::LOG_MESSAGE_PREFIX.'deleteFromQueue - could not delete message "'.$message->amazonSqsId.'" from queue "'.$fullQueueUrl.'" because of error => "'.$result->body->Error->Message.'".');
            return false;
        }

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'deleteFromQueue - message "'.$fullQueueUrl.'" successfully deleted from queue "'.$fullQueueUrl.'".');
        }
        return true;
    }

    public function retrieveFromQueue(SqsMessageBase $message) {
        
      if($this->object_storage == 'HpObjectStorage') {
         $queueName = $message->getQueueName();
         $this->logger->debug(self::LOG_MESSAGE_PREFIX.'retrieveFromHPQueue - retrieving message from queue "'.$queueName.'".');
         $result = $this->hpCloudObj->getMessageFromQueue($queueName,$this->hpcloud_messaging_url);
         // if there is error 
         if($result == ''){
          $this->logger->err(self::LOG_MESSAGE_PREFIX.'retrieveFromHPQueue - could not retrieve message from queue "'.$queueName);
            return null;
         }
        if($result['message'] != '') {
            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'retrieveFromHPQueue - queue is empty "'.$queueName.'"'.$result['message']);
            return null;
        } 
      
       // $message   = unserialize(base64_decode($result->body->ReceiveMessageResult->Message->Body));
       $message = $result;
       $this->logger->debug(self::LOG_MESSAGE_PREFIX.'retrieveFromHPQueue - message successfully retrieved from queue "'.$queueName.'".'); 
        }
      else {

        $fullQueueUrl = $this->getFullQueueUrl($message);

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'retrieveFromQueue - retrieving message from queue "'.$fullQueueUrl.'".');

        $result = $this->sqsClient->receive_message($fullQueueUrl);

        if (!$result->isOK()) {
            $this->logger->err(self::LOG_MESSAGE_PREFIX.'retrieveFromQueue - could not retrieve message from queue "'.$fullQueueUrl.'" because of error => "'.$result->body->Error->Message.'".');
            return null;
        }
      

        if (!$result->body->ReceiveMessageResult->Message->Body) {
            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'retrieveFromQueue - queue is empty "'.$fullQueueUrl.'".');
            return null;
        }

        $message                      = unserialize(base64_decode($result->body->ReceiveMessageResult->Message->Body));
        $messageIdInfo                = $result->body->ReceiveMessageResult->Message->MessageId->to_array();
        $message->amazonSqsId         = $messageIdInfo[0];
        $receiptHandleInfo            = $result->body->ReceiveMessageResult->Message->ReceiptHandle->to_array();
        $message->amazonReceiptHandle = $receiptHandleInfo[0];

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'retrieveFromQueue - message "'.$message->amazonSqsId.'" successfully retrieved from queue "'.$fullQueueUrl.'".');
      }

        return $message;
    }

    public function getMessageCount(SqsMessageBase $message) {

        $fullQueueUrl = $this->getFullQueueUrl($message);

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'getMessageCount - retrieving count from queue "'.$fullQueueUrl.'".');

        $result = $this->sqsClient->get_queue_size($fullQueueUrl);

        if (!is_integer($result)) {
            if (!$result->isOK()) {
                $this->logger->err(self::LOG_MESSAGE_PREFIX.'getMessageCount - could not retrieve count from queue "'.$fullQueueUrl.'" because of error => "'.$result->body->Error->Message.'".');
                return null;
            }
        }

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'getMessageCount - count "'.$result.'" successfully retrieved from queue "'.$fullQueueUrl.'".');

        return $result;
    }
}
