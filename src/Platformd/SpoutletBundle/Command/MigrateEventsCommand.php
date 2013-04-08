<?php

namespace Platformd\SpoutletBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use
    DateTime,
    DateTimeZone
;

use
    Platformd\EventBundle\Entity\GlobalEvent,
    Platformd\MediaBundle\Entity\Media
;

class MigrateEventsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('awa:events:migrate')
            ->setDescription('Converts old events to the new system')
            ->setHelp(<<<EOT
The <info>awa:events:migrate</info> command takes all old-style events and migrates them to the new events system:

  <info>php app/console awa:events:migrate</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container      = $this->getContainer();
        $em             = $container->get('doctrine')->getEntityManager();
        $userManager    = $container->get('platformd_user.manager');
        $eventUser      = $userManager->loadUserByName('jluong1');

        $output->write('Getting old events...');

        $abstractEventRepo = $em->getRepository('SpoutletBundle:AbstractEvent');
        $events = $abstractEventRepo->findAll();

        if (!$events) {
            $output->writeLn('<comment>✔</comment>');
            $output->writeLn(' - No events found');
            $output->writeLn('');
            exit;
        }

        $eventsArr = array();

        foreach ($events as $event) {
            if ($event instanceof Event and !$event->getProcessed()) {
                $eventsArr[] = $event;
            }
        }

        $eventCount = count($eventsArr);

        $output->writeLn('<info>✔</info>');
        $output->writeLn(' - '.$eventCount.' events found.');
        $output->writeLn('');

        $output->writeLn('Migrating '.$eventCount.' events...');

        foreach ($eventsArr as $event) {

            // TODO - banner images, general image

            $globalEvent = new GlobalEvent();
            $globalEvent->setName($event->getName());
            $globalEvent->setSlug($event->getSlug());
            $globalEvent->setStartsAt($event->getStartsAt());
            $globalEvent->setEndsAt($event->getEndsAt());
            $globalEvent->setPublished($event->getPublished());
            $globalEvent->setHostedBy($event->getHostedBy());
            $globalEvent->setCreatedAt($event->getCreated());
            $globalEvent->setUpdatedAt($event->getUpdated());
            $globalEvent->setContent($event->getContent());
            $globalEvent->setTimezone($event->getTimezone());
            $globalEvent->setExternalUrl($event->getExternalUrl());
            $globalEvent->setDisplayTimezone($event->getDisplayTimezone());
            $globalEvent->setLocation($event->getLocation());
            $globalEvent->setAddress2($event->getCity().', '.$event->getCountry());
            $globalEvent->setActive($event->getReady);
            $globalEvent->setSites($event->getSites());

            $globalEvent->setApproved(true);
            $globalEvent->setOnline(false);
            $globalEvent->setAddress1('');
            $globalEvent->setRegistrationOption('REGISTRATION_3RDPARTY');
            $globalEvent->setAttendeeCount(0);
            $globalEvent->setUser($eventUser);

            $banner = new Media();
            $banner->setFilename('banner/'.$event->getBannerImage());
            $banner->setCreatedAt(new DateTime());
            $banner->setUpdatedAt(new DateTime());
            $banner->setMimeType('image/jpeg');
            $banner->setOwner($eventUser);
            $banner->setLocale($event->getLocale() ?: 'en');

            $em->persist($banner);
            $em->flush();

            $globalEvent->setBanner($banner);

            $em->persist($globalEvent);
            $em->flush();
        }

        $output->writeLn('');
        $output->writeLn('Finished migrating '.$eventCount.' events!');
        $output->writeLn('');
    }
}
