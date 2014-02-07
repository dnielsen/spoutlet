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

class MassEmailQueueProcessorCommand extends BaseCommand
{
    const ITERATION_COUNT = 25;

    protected function configure()
    {
        $this
            ->setName('pd:massEmails:process')
            ->setDescription('Processes mass emails queued to be sent via Amazon SES.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command gets a list of mass emails to be sent from an Amazon SQS queue and queues emails to be sent in batches.

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

        while ($message = $queueUtil->retrieveFromQueue(new MassEmailQueueMessage())) {

            $iterationCount++;

            if ($iterationCount > self::ITERATION_COUNT) {
                $this->output();
                $this->output(0, 'Maximum iterations reached - exiting.');
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

            $this->output(3, 'Queueing emails to recipients in chunks of '.ChunkedMassEmailQueueMessage::RECIPIENT_CHUNK_SIZE);

            $sendCount = $emailManager->queueEmails($email);

            $this->output(3, 'Email sent to '.$sendCount.' recipients.');
            $this->deleteMessageWithOutput($message);
        }

        $this->output();
        $this->output(1, 'No more emails.');

        $this->outputErrors();

        $this->output(0);
    }
}
