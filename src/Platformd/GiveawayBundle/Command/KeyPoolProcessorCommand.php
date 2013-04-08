<?php

namespace Platformd\GiveawayBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Finder\Finder;

use Platformd\GiveawayBundle\Pool\PoolLoader;
use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\GiveawayBundle\Entity\DealPool;
use Platformd\GiveawayBundle\QueueMessage\KeyPoolQueueMessage;

/**
 * Command that places themes assets into a given directory.
 */
class KeyPoolProcessorCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('pd:keypool:process')
            ->setDescription('Gets the current keypools queued for processing from Amazon SQS and adds them to the database')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command pulls the queued giveaway and deal keypools from an Amazon SQS queue and processes the files.

<info>php %command.full_name%</info>

The files will be fetched from Amazon S3 and the keys contained within will be added to the correct key pool.
Once complete, the person responsible for adding the pool will be notified by email.

EOT
            )
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $s3             = $this->getContainer()->get('aws_s3');
        $sqs            = $this->getContainer()->get('aws_sqs');
        $userManager    = $this->getContainer()->get('fos_user.user_manager');
        $queue_url      = $this->getContainer()->getParameter('queue_prefix').KeyPoolQueueMessage::QUEUE_NAME;;
        $connection     = $this->getContainer()->get('database_connection');
        $poolLoader     = new PoolLoader($connection);
        $em             = $this->getContainer()->get('doctrine')->getEntityManager();

        $tick           = "<info>✔</info>";
        $cross          = "<fg=red>✘</fg=red>";
        $yellowCross    = "<fg=yellow>✘</fg=yellow>";

        $output->write("\nRetrieving message from queue...");

        // Get the first message in the queue
        $messageResponse = $sqs->receive_message($queue_url, array(
            'VisibilityTimeout' => 5,
        ));

        if ($messageResponse->isOk()) { // We retrieved a message from the queue

            $message        = json_decode($messageResponse->body->ReceiveMessageResult->Message->Body);
            $receiptHandle  = $messageResponse->body->ReceiveMessageResult->Message->ReceiptHandle;

            if ($message) {

                $user = $userManager->findUserBy(array('id' => $message->userId));

                $output->writeLn($tick);

                $output->write("\n - <comment>Bucket</comment> \t- ".$message->bucket);
                $output->write("\n - <comment>Filename</comment> \t- ".$message->filename);
                $output->write("\n - <comment>Site Id</comment> \t- ".$message->siteId);
                $output->write("\n - <comment>User Id</comment> \t- ".$message->userId);
                $output->write("\n - <comment>Pool Id</comment> \t- ".$message->poolId);
                $output->write("\n - <comment>Pool Class</comment> \t- ".$message->poolClass."\n");

                $output->write("\nGetting key file from S3...");

                $repo       = $em->getRepository('GiveawayBundle:'.$message->poolClass);
                $keyRepo    = $em->getRepository('GiveawayBundle:'.KeyPoolQueueMessage::$classKeyEntityMap[$message->poolClass]);
                $pool       = $repo->find($message->poolId);

                $response   = $s3->get_object($message->bucket, $message->filename);

                if ($response->isOk()) { // We retrieved the key file from S3

                    $output->writeLn($tick);
                    $output->write("Finding key pool...");

                    if ($pool) {

                        $output->writeLn($tick);

                        $keys = $response->body;
                        $keysArr = explode("\n", $keys);

                        foreach ($keysArr as $id => $key) {
                            if (empty($key)) {
                                unset($keysArr[$id]);
                            }
                        }

                        $keyCount = count($keysArr);

                        $output->write("Loading ".$keyCount." keys into database...");

                        try {
                            $poolLoader->loadKeysFromArray($keysArr, $pool, KeyPoolQueueMessage::$classTypeMap[$message->poolClass]);
                        } catch(Exception $e) {
                            $output->writeLn($cross);
                            $output->write("\nException thrown during database loading:\n");
                            $output->write("\n\t<error>".$e->getMessage()."</error>\n");
                            $output->writeLn('');

                            $this->sendErrorEmail($user, $output, $pool);

                            $output->write("Deleting key pool from database...");

                            $em->remove($pool);
                            $em->flush();

                            $output->writeLn($tick);

                            $this->deleteSqsMessage($sqs, $queue_url, $receiptHandle, $output);
                            exit;
                        }

                        $output->writeLn($tick);

                        $output->write("Checking key count...");

                        $keyCount = count($keysArr);
                        $keysAdded = $keyRepo->getTotalForPool($pool);

                        if ($keyCount == $keysAdded) {

                            $output->writeLn($tick);
                            $this->sendSuccessEmail($user, $output, $pool);

                        } else {

                            $output->writeLn($cross);
                            $output->write("\n\t<error>".$keyCount.' keys expected, but '.$keysAdded.' added'."</error>\n");
                            $output->writeLn('');

                            $this->sendErrorEmail($user, $output, $pool);

                            $output->write("Deleting key pool from database...");

                            $em->remove($pool);
                            $em->flush();

                            $output->writeLn($tick);
                        }

                    } else {

                        $output->writeLn($cross);
                        $output->write("\n\t<error>Key pool was not found in the database.</error>\n");
                        $output->writeLn('');

                        $this->sendErrorEmail($user, $output, $pool);
                    }

                } else {

                    $output->writeLn($cross);
                    $output->write("\nAn error occurred whilst downloading key file from S3:\n");

                    if ($response) {
                        if ($response->status == 404) {
                            $output->write("\n\t<error>File not found on S3.</error>\n");
                        } else {
                            $output->write("\n\t<error>".$response->body->Error->Message."</error>\n");
                        }
                    }

                    $output->writeLn('');

                    $this->sendErrorEmail($user, $output, $pool);

                    $output->write("Deleting key pool from database...");

                    $em->remove($pool);
                    $em->flush();

                    $output->writeLn($tick);
                }

                $this->deleteSqsMessage($sqs, $queue_url, $receiptHandle, $output);

            } else {

                $output->writeLn($yellowCross);
                $output->write(" - <comment>No messages queued.</comment>\n");
                $output->writeLn('');
            }

        } else {

            $output->writeLn($cross);
            $output->write("\n\t<error>An error occurred whilst retrieving the message.</error>\n");
            $output->writeLn('');
        }
    }

    private function sendErrorEmail($user, $output, $pool = null)
    {
        $emailManager   = $this->getContainer()->get('platformd.model.email_manager');
        $translator     = $this->getContainer()->get('translator');
        $tick           = "<info>✔</info>";
        $cross          = "<fg=red>✘</fg=red>";

        $output->write("Emailing keyfile uploader...");

        $emailTo = $user->getEmail();
        $subject = $translator->trans('platformd.giveaway_pool.admin.queue_process_error.title', array(), 'messages', $user->getLocale() ?: 'en');
        $message = $translator->trans('platformd.giveaway_pool.admin.queue_process_error.message', array(
            '%poolName%'    => $pool ? $pool->getDescription() : 'Unknown pool name',
            '%parentName%'  => $pool ? $pool->getParentName() : 'Unknown name',
        ), 'messages', $user->getLocale());

        $emailManager->sendEmail($emailTo, $subject, $message);

        $output->writeLn($tick);
    }

    private function sendSuccessEmail($user, $output, $pool)
    {
        $emailManager   = $this->getContainer()->get('platformd.model.email_manager');
        $translator     = $this->getContainer()->get('translator');
        $tick           = "<info>✔</info>";
        $cross          = "<fg=red>✘</fg=red>";

        $output->write("Emailing keyfile uploader...");

        $emailTo = $user->getEmail();
        $subject = $translator->trans('platformd.giveaway_pool.admin.queue_processed_email.title', array(), 'messages', $user->getLocale() ?: 'en');
        $message = $translator->trans('platformd.giveaway_pool.admin.queue_processed_email.message', array(
            '%poolName%'    => $pool->getDescription(),
            '%parentName%'  => $pool->getParentName(),
        ), 'messages', $user->getLocale());

        $emailManager->sendEmail($emailTo, $subject, $message);

        $output->writeLn($tick);
    }

    private function deleteSqsMessage($sqs, $queue_url, $receiptHandle, $output)
    {
        $tick  = "<info>✔</info>";
        $cross = "<fg=red>✘</fg=red>";

        $output->write("Deleting message from queue...");

        $deleteResponse = $sqs->delete_message($queue_url, $receiptHandle);

        if ($deleteResponse->isOK()) {
            $output->writeLn($tick);
        } else {
            $output->writeLn($cross);
            $output->write("\nThere was an error when trying to delete the message from the queue.\n");
            $output->write("\n\t<error>".$deleteResponse->body->Error->Message."</error>\n");
        }

        $output->writeLn('');
    }
}
