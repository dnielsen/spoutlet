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

use HPCloud\HPCloudPHP;

class DeleteAllDocumentsCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $searchManager;
    private $hpObject;
    private $hpcloud;

    const SLEEP_MILLISECONDS = 10;

    protected function configure()
    {
        $this
            ->setName('pd:search:deleteAll')
            ->setDescription('Deletes all indexed search documents from Amazon CloudSearch')
            ->addOption('confirm-delete', null, InputOption::VALUE_NONE, 'Required to make this command do anything.' )
            ->setHelp(<<<EOT
The <info>pd:search:deleteAll</info> command iterates around all Amazon CloudSearch documents and deletes them in batches:

  <info>php app/console pd:search:deleteAll</info>
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

    protected function error($message)
    {
        $this->output(0);
        $this->output(0, '<error>'.$message.'</error>');
        $this->output(0);
        exit;
    }

    protected function uploadBatch($batchData)
    {
        $this->output(4, 'Uploading batch to CloudSearch...', false);
        $result = $this->searchManager->document(json_encode($batchData));

        if (isset($result['status']) && $result['status'] == 'error') {
            $this->output(0);
            $this->output(0);
            $this->output(0, 'Amazon CloudSearch returned an error when uploading batch data:');
        }

        $this->tick();
    }

    protected function clearQueue()
    {
        $queueUtil = $this->getContainer()->get('platformd.util.queue_util');

        while ($message = $queueUtil->retrieveFromQueue(new SearchIndexQueueMessage())) {
            if ($this->hpObject == 1){
                $response = $this->hpcloud->delete_object($message->bucket, $message->filename);
            } else {
                $s3        = $this->getContainer()->get('aws_s3');
                $response = $s3->delete_object($message->bucket, $message->filename);
            }    
            $deleted = $queueUtil->deleteFromQueue($message);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();
        $this->searchManager = $container->get('platformd.model.search_manager');
      
        $confirmDelete       = $input->getOption('confirm-delete');

        $this->output(0);
        $this->output(0, 'PlatformD Indexed Search Document Deleter');
        $this->output(0);
        
        $this->hpObject = 0;
        if($this->getContainer()->getParameter('object_storage') == 'HpObjectStorage')
        {
          $hpcloud_accesskey = $this->getContainer()->getParameter('hpcloud_accesskey');
          $hpcloud_secreatekey = $this->getContainer()->getParameter('hpcloud_secreatkey');
          $hpcloud_tenantid = $this->getContainer()->getParameter('hpcloud_tenantid');

          $this->hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);
          $this->hpObject = 1 ;
        }

        if (!$confirmDelete) {
            $this->error('Please use --confirm-delete to confirm deletion of all search index documents.');
        }

        if ($confirmDelete) {

            $env     = $container->getParameter('kernel.environment');
            $devMode = $container->getParameter('search_dev_mode');
            $devUser = $container->getParameter('search_dev_user');

            if ($env !== 'prod') {
                if (!$devMode) {
                    $this->error('You are running this command in the dev environment but search_dev_mode is set to false.');
                }

                if (!$devUser) {
                    $this->error('You are running this command in the dev environment but have not specified a search_dev_user parameter.');
                }
            }

            $this->output(0, 'Clearing queue...', false);
            $this->clearQueue();
            $this->tick();
            $this->output(0);

            $this->output(0, 'Deleting all indexed documents...');
            $this->output(2, 'Getting documents from CloudSearch...', false);

            $documents = $this->searchManager->getAllDocuments();
            $batchData = array();

            $hits           = $documents['hits'];
            $resultCount    = $hits['found'];

            if ($resultCount > 0) {
                $this->tick();
                $this->output(4, 'Found '.$resultCount.' documents to delete - processing...');

                foreach ($hits['hit'] as $document) {

                    usleep(self::SLEEP_MILLISECONDS);

                    $indexData = array(
                        'type'    => 'delete',
                        'id'      => $document['id'],
                        'version' => time(),
                    );

                    $updatedBatchData   = $batchData;
                    array_push($updatedBatchData, $indexData);

                    if (strlen(json_encode($updatedBatchData)) <= (4*1024*1024)) {
                        // Updated data is still within 4MB batch limit (Amazon limit to 5MB but this caused problems in testing) - append
                        $batchData = $updatedBatchData;
                    } else {

                        $this->output(0);

                        // Batch would be too large if we append - Upload processed data to CloudSearch and reset data
                        $this->uploadBatch($batchData);
                        $batchData = $indexData;
                    }
                }

                if (count($batchData) > 0) {
                    $this->output(0);
                    $this->uploadBatch($batchData);
                }

            } else {
                $this->tick();
                $this->output(4, 'No documents to delete.');
            }
        }

        $this->output(0);
    }
}
