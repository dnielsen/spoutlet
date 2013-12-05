<?php

namespace Platformd\SearchBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;
use Platformd\SearchBundle\QueueMessage\SearchIndexQueueMessage;
use Platformd\SpoutletBundle\HPCloud\HPCloudPHP;
class SearchIndexQueueProcessorCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $searchManager;
    private $hpObject;
    const DELAY_BETWEEN_KEYS_MILLISECONDS = 50;

    protected function configure()
    {
        $this
            ->setName('pd:search:processQueue')
            ->setDescription('Processes the search indexing requests that are currently queued')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command retrieves items from the search index queue and processes them.

  <info>php %command.full_name%</info>
EOT
            );
    }

    protected function output($indentationLevel = 0, $message = null, $withNewLine = true) {

        if ($message === null) {
            $message = '';
        }

        if ($withNewLine) {
            $this->stdOutput->writeLn(str_repeat(' ', $indentationLevel).$message);
        } else {
            $this->stdOutput->write(str_repeat(' ', $indentationLevel).$message);
        }
    }

    protected function tick()
    {
        $this->output(0, '<info>✔</info>');
    }

    protected function error($message, $exit = false)
    {
        $this->output(0);
        $this->output(0, '<error>'.$message.'</error>');
        $this->output(0);

        if ($exit) {
            exit;
        }
    }

    protected function uploadBatch($batchData)
    {
        $this->output(4, 'Uploading batch to CloudSearch...', false);
        $result = $this->searchManager->document($batchData);

        if (isset($result['status']) && $result['status'] == 'error') {
            $this->output(0);
            $this->output(0);
            $this->output(0, 'Amazon CloudSearch returned an error when uploading batch data:');
            var_dump($result);exit;
        }

        $this->tick();
    }

    protected function deleteMessageWithOutput($message)
    {
        $queueUtil    = $this->getContainer()->get('platformd.util.queue_util');

        $this->output(2, 'Deleting message from queue.');
        $this->output(2, ($queueUtil->deleteFromQueue($message) ? 'Message deleted successfully.' : 'Unable to delete message.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();
        $this->searchManager = $container->get('platformd.model.search_manager');
        
        $s3                  = $container->get('aws_s3');
        $queueUtil           = $container->get('platformd.util.queue_util');

        $this->output(0);
        $this->output(0, 'PlatformD Search Index Queue Processor');
        $this->output(0);

        $this->output(0, 'Processing queue for search index documents.');
        $this->hpObject = 0;
        if($this->getContainer()->getParameter('object_storage') == 'HpObjectStorage')
        {
          $hpcloud_accesskey = $this->getContainer()->getParameter('hpcloud_accesskey');
          $hpcloud_secreatekey = $this->getContainer()->getParameter('hpcloud_secreatkey');
          $hpcloud_tenantid = $this->getContainer()->getParameter('hpcloud_tenantid');

          $this->hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);
          $this->hpObject = 1 ;
        }


        while ($message = $queueUtil->retrieveFromQueue(new SearchIndexQueueMessage())) {

            usleep(self::DELAY_BETWEEN_KEYS_MILLISECONDS);

            $deleteMessage = true;

            $this->output();
            $this->output(2, $message);

            $this->output(4, 'Retrieving message data from s3...', false);
            $response='';
            
            if($this->hpObject == 1) {
              $response_data = $this->hpcloud->get_object($message->bucket,$message->filename);
            } else {
              $response = $s3->get_object($message->bucket, $message->filename);
              $response_data = $response->isOk();
            }
            
                       
            if ($response_data) { // We retrieved the json file from S3

                $this->tick();
                $this->output(4, 'Sending document to CloudSearch for indexing...');

                $indexData =  $response->body;

                $indexDataArr = json_decode($indexData, true);

                $this->output(6, 'Document - { ID => "'.$indexDataArr[0]['id'].'" }');

                $result = $this->searchManager->document($indexData);

                if (isset($result['status']) && $result['status'] == 'error') {
                    $this->output(6, 'Amazon CloudSearch returned an error when uploading batch data:');
                    $this->error($result['errors'][0]['message']);
                    $deleteMessage = false;
                } else {
                    $this->output(4, 'Deleting data from s3...', false);
                    if($this->hpObject == 0) {
                      $response = $s3->delete_object($message->bucket, $message->filename);
                      $response_data = $response;
                    } else {
                      $response = $this->hpcloud->delete_object($message->bucket, $message->filename);
                      $response_data = $response->isOk(); 
                    }         
                    if ($response_data) {
                        $this->tick();
                    } else {
                        $this->output();
                        $this->output(6, "An error occurred whilst deleting index data file from S3:");

                        if ($response) {
                            $this->error($response->status == 404 ? "File not found on S3." : $response->body->Error->Message);
                        }
                    }
                }

            } else {

                $this->output();
                $this->output(6, "An error occurred whilst downloading index data file from S3:");

                if ($response) {
                    if ($response->status == 404) {
                        $this->error("File not found on S3.");
                    } else {
                        $this->error($response->body->Error->Message);
                        $deleteMessage = false;
                    }
                }
            }

            if ($deleteMessage) {
                $this->deleteMessageWithOutput($message);
            }
        }
        $this->output();
        $this->output(2, 'No more messages in queue.');

        $this->output(0);
    }
}
