<?php

namespace Platformd\SpoutletBundle\Command;

use Platformd\SpoutletBundle\Command\BaseCommand,
    Platformd\SpoutletBundle\QueueMessage\MassEmailQueueMessage,
    Platformd\SpoutletBundle\QueueMessage\ChunkedMassEmailQueueMessage
;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use DateTime;

class ChunkedMassEmailQueueProcessorCommand extends BaseCommand
{
    const ITERATION_COUNT        = 5;
    const ALL_EMAILS_TARGET_TIME = 600;

    protected function configure()
    {
        $this
            ->setName('pd:massEmails:sendChunks')
            ->setDescription('Processes mass emails queued to be sent via Amazon SES.')
            ->addOption('spawn-more', null, InputOption::VALUE_NONE, 'Calculates an approximate email count to be sent and spawns additional processes to handle these more quickly.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command gets a list of mass emails to be sent from an Amazon SQS queue and sends them.

  <info>php %command.full_name%</info>
EOT
            );
    }

    protected function deleteMessageWithOutput($message)
    {
        $queueUtil    = $this->getContainer()->get('platformd.util.queue_util');

        $this->output(2, 'Deleting message from queue.');
        $this->output(2, ($queueUtil->deleteFromQueue($message) ? 'Message deleted successfully.' : 'Unable to delete message.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput = $output;
        $container       = $this->getContainer();
        $em              = $container->get('doctrine')->getEntityManager();
        $queueUtil       = $container->get('platformd.util.queue_util');
        $emailManager    = $container->get('platformd.model.email_manager');

        $this->output();
        $this->output(0, 'PlatformD Mass Email Queue Processor');

        $this->output();
        $this->output(0, 'Processing queue for the Key Requests.');

        $iterationCount = 0;
        $start          = time();

        if ($input->getOption('spawn-more')) {

            $alreadyRunning = exec('ps aux | grep "pd:massEmails:sendChunks --spawn-more" | grep -v "grep" | wc -l');

            $break = false;

            if ($alreadyRunning > 1) {
                $this->output(0, 'Email processor with "spawn-more" privileges is already running. Will not launch any more instances.');
                $break = true;
            }

            if (!$break) {
                $this->output();
                $this->output(0, 'Getting message count.');

                $messageCount = $queueUtil->getMessageCount(new ChunkedMassEmailQueueMessage());

                if (!$messageCount || $messageCount < 1) {
                    $this->output(1, 'No messages.');
                } else {

                    $this->output(1, $messageCount.' messages on SQS.');

                    $emailCount     = ChunkedMassEmailQueueMessage::RECIPIENT_CHUNK_SIZE * $messageCount;
                    $numProcesses   = ceil($emailCount / (self::ALL_EMAILS_TARGET_TIME/1.11)); // Benchmarking results find a speed of approx. 1.11 emails/second per process.
                    $alreadyRunning = exec('ps aux | grep "pd:massEmails:sendChunks" | grep -v "grep" | wc -l');

                    $needed = $numProcesses - $alreadyRunning;
                    $needed = $needed > 20 ? 20 : $needed;

                    $this->output(1, $numProcesses.' processes required - '.$alreadyRunning.' already running, '.$needed.' needed.');

                    if ($needed > 0) {

                        $dirParts = explode('src/', dirname(__FILE__));

                        for ($i=0; $i < $needed; $i++) {
                            $this->output(2, 'Spawning process '.($i + 1).' of '.$needed.'.');
                            shell_exec('php '.$dirParts[0].'app/console pd:massEmails:sendChunks > /dev/null 2>/dev/null &');
                        }
                    }
                }
            }
        }

        while ($message = $queueUtil->retrieveFromQueue(new ChunkedMassEmailQueueMessage())) {

            $iterationCount++;

            if ($iterationCount > self::ITERATION_COUNT) {
                $this->output();
                $this->output(0, 'Maximum iterations reached - exiting after '.(time() - $start).' seconds.');
                exit;
            }

            if ($this->exitAfterCurrentItem) {
                $this->output();
                $this->output(0, 'Process terminated - exiting.');
                exit;
            }

            $this->output();
            $this->output(0, 'Iteration '.$iterationCount);

            $this->output();
            $this->output(1, 'Processing message.');

            $this->output(2, $message);

            if (!$message->hasValidEmailType()) {
                $this->output(2, 'Unknown email type = "'.$message->emailType.'".');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $repoClass = MassEmailQueueMessage::$typeClassMap[$message->emailType];
            $repo = $this->getRepo($repoClass);

            $email = $repo->find($message->emailId);

            if (!$email) {
                $this->output(2, 'Unable to find email with ID ['.$message->emailId.'].');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $this->output(3, 'Sending emails to recipients.');

            $sendCount = $emailManager->sendMassEmail($email, $message);

            $this->output(3, 'Email sent to '.$sendCount.' recipients.');
            $this->deleteMessageWithOutput($message);
        }

        $this->output();
        $this->output(0, 'Finished after '.(time() - $start).' seconds.');
        $this->output(1, 'No more emails.');

        $this->outputErrors();

        $this->output(0);
    }
}
