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
        $eventRepo      = $container->get('platformd_event.repository.global_event');
        $siteRepo       = $em->getRepository('SpoutletBundle:Site');

        $output->write('Getting old events...');

        try {

            $dsn = 'mysql:dbname=;host='.$this->getContainer()->getParameter('database_host');
            $user = $this->getContainer()->getParameter('database_user');
            $password = $this->getContainer()->getParameter('database_password');
            $dbh = new \PDO($dsn, $user, $password);

            $sql    = 'SELECT * FROM `alienware_production`.`event` e LEFT JOIN `alienware_production`.`pd_event_site` s ON `s`.`abstractevent_id` = e.`id` WHERE e.`discr`="event"';
            $events = $dbh->query($sql);

        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }


        if (count($events) < 1) {
            $output->writeLn('<comment>✔</comment>');
            $output->writeLn(' - No events found');
            $output->writeLn('');
            exit;
        }

        $newEvents = $eventRepo->findAll();

        $newSlugs = array();

        foreach ($newEvents as $newEvent) {
            $newSlugs[] = $newEvent->getSlug();
        }

        $eventsArr = array();

        foreach ($events as $event) {
            if (!in_array($event['slug'], $newSlugs)) {
                $eventsArr[] = $event;
            }
        }

        $eventCount = count($eventsArr);

        $output->writeLn('<info>✔</info>');
        $output->writeLn(' - '.$eventCount.' events found.');
        $output->writeLn('');

        $output->writeLn('Migrating '.$eventCount.' events...');

        foreach ($eventsArr as $event) {

            $globalEvent = new GlobalEvent();
            $globalEvent->setName($event['name']);
            $globalEvent->setSlug($event['slug']);
            $globalEvent->setStartsAt($event['starts_at']);
            $globalEvent->setEndsAt($event['ends_at']);
            $globalEvent->setPublished($event['published']);
            $globalEvent->setHostedBy($event['hosted_by']);
            $globalEvent->setCreatedAt($event['created']);
            $globalEvent->setUpdatedAt($event['updated']);
            $globalEvent->setContent($event['content']);
            $globalEvent->setTimezone($event['timezone']);
            $globalEvent->setExternalUrl($event['external_url']);
            $globalEvent->setDisplayTimezone($event['display_timezone']);
            $globalEvent->setLocation($event['location']);
            $globalEvent->setAddress2($event['city'].', '.$event['country']);
            $globalEvent->setActive($event['ready']);
            $globalEvent->setSites(array($siteRepo->find($event['site_id'])));

            $globalEvent->setApproved(true);
            $globalEvent->setOnline(false);
            $globalEvent->setAddress1('');
            $globalEvent->setRegistrationOption('REGISTRATION_3RDPARTY');
            $globalEvent->setAttendeeCount(0);
            $globalEvent->setUser($eventUser);

            $banner = new Media();
            $banner->setFilename('banner/'.$event['bannerImage']);
            $banner->setCreatedAt(new DateTime());
            $banner->setUpdatedAt(new DateTime());
            $banner->setMimeType('image/jpeg');
            $banner->setOwner($eventUser);
            $banner->setLocale($event['locale'] ?: 'en');

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
