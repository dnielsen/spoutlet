<?php

namespace Platformd\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SendEventEmailRemindersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('platformd:events:reminders')
            ->setDescription('Sends a reminder email to attendees of events')
            ->addOption('delete-all', null, InputOption::VALUE_NONE, 'If set, the command will first purge all documents from CloudSearch' )
            ->setHelp(<<<EOT
Checks upcoming group and global events and sends a reminder email to each attendee of those starting <info>[days]</info> days from today:

<comment>php app/console platformd:events:reminders 7</comment>

This example above will check for any events starting 7 days from today, and send a reminder email to all attendees.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $daysOffset = abs((int) $input->getArgument('days'));

        $em                 = $container->get('doctrine')->getEntityManager();
        $groupEventService  = $container->get('platformd_event.service.group_event');
        $globalEventService = $container->get('platformd_event.service.global_event');

        $output->writeLn('');

        $output->write('Getting group events...');
        $groupEvents = $groupEventService->findUpcomingEventsStartingDaysFromNow($daysOffset);
        $output->write('<info>✔</info>', true);

        $output->write('Getting global events...');
        $globalEvents = $globalEventService->findUpcomingEventsStartingDaysFromNow($daysOffset);
        $output->write('<info>✔</info>', true);

        if ($groupEvents) {

            $output->writeLn('');
            $output->writeLn('Building group event emails...');

            foreach ($groupEvents as $groupEvent) {
                $site = $groupEvent->getSites()->first();

                $output->write('  - Sending email for <comment>'.$groupEvent->getName().'</comment>...');
                $groupEventService->sendReminderEmail($groupEvent, $site);
                $output->write('<info>✔</info>', true);
            }
        } else {
            $output->writeLn('  - No group events requiring reminders.');
        }

        if ($globalEvents) {

            $output->writeLn('');
            $output->writeLn('Building global event emails...');

            foreach ($globalEvents as $globalEvent) {
                $site = $globalEvent->getSites()->first();

                $output->write('  - Sending email for <comment>'.$globalEvent->getName().'</comment>...');
                $groupEventService->sendReminderEmail($globalEvent, $site);
                $output->write('<info>✔</info>', true);
            }
        } else {
            $output->writeLn('  - No global events requiring reminders.');
        }

        $output->writeLn('');
        $output->writeLn('Done.');
    }
}
