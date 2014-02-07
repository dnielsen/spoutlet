<?php

/**
 * RabbitMQ Class
 *
 * @package   	RabbitMQ
 * @category  	Libraries
 * @author	Nayak Kamal
 */
namespace Platformd\SpoutletBundle\RabbitMQ;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMq
{
    public $connection;
    public $channel;
    public $result;
    public $msgData = array();
    
    function __construct($hostName=null,$port=null,$userName=null,$password=null)
    { 
      $this->connection = new AMQPConnection($hostName, $port, $userName, $password);      
      $this->channelObj = $this->connection->channel();     
    }
    
    function addToQueue($queueName='',$message='')
    {       
      $this->channelObj->queue_declare($queueName, false, false, false, true);
      $msg = new AMQPMessage($message);
      $this->channelObj->basic_publish($msg, '', $queueName);
      return 'Message inserted in Queue Sucessfuly';
    }
    
    public function getMsgData()
    {
      return $this->msgData;
    }
    public function setMsgData($msgData)
    {
      $this->msgData[] = $msgData;
    } 
    public function processMsg(AMQPMessage $message)
    {
      //echo 'Process Message is Called ';
      $this->setMsgData($message->body);  
      return true;
    }
    
    function receiveFromQueue($queueName)
    {
      $this->channelObj->queue_declare($queueName, false, false, false, true);
      $this->channelObj->basic_consume($queueName, '', false, false, false, false, array($this,'processMsg'));
      
      while(count($this->channelObj->callbacks) >0) {        
          try {
               $this->channelObj->wait(null,false,2);
               break;
              } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
               break;
              }
      }
      $msgData = $this->getMsgData();
      // unset the value of msgData 
      //var_dump($msgContent);
      $this->msgData = array();
      return (isset($msgData[0]) ) ? $msgData[0] : $msgData;
    }
  
}
?>
