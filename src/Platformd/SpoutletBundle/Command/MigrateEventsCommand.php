<?php

namespace Platformd\SpoutletBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Doctrine\Common\Collections\ArrayCollection;

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
        $userManager    = $container->get('fos_user.user_manager');
        $eventUser      = $userManager->findUserByUsername('admin');
        $eventRepo      = $container->get('platformd_event.repository.global_event');
        $siteRepo       = $em->getRepository('SpoutletBundle:Site');
        $eventService   = $container->get('platformd_event.service.global_event');

        $output->write('Getting old events...');

        try {

            $dsn = 'mysql:dbname=;host='.$this->getContainer()->getParameter('database_host');
            $user = $this->getContainer()->getParameter('database_user');
            $password = $this->getContainer()->getParameter('database_password');
            $dbh = new \PDO($dsn, $user, $password);

            $sql    = 'SELECT * FROM `'.$this->getContainer()->getParameter('database_name').'`.`event` e LEFT JOIN `'.$this->getContainer()->getParameter('database_name').'`.`pd_event_site` s ON `s`.`abstractevent_id` = e.`id` WHERE e.`discr`="event" AND s.`site_id` = 2';
            $events = $dbh->query($sql);

        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }


        if (!$events || count($events) < 1) {
            $output->writeLn('<info>✔</info>');
            $output->writeLn(' - No events found');
            $output->writeLn('');
        } else {

            $locations  = array();
            $hosts      = array();

            if (($handle = fopen("/home/ubuntu/old_events_locations.csv", "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $locations[$data[0]]    = trim($data[1]);
                    $hosts[$data[0]]        = trim($data[2]);
                }
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

            if ($eventCount > 0) {
                $output->write(' - Migrating '.$eventCount.' events...');

                foreach ($eventsArr as $event) {

                    $globalEvent = new GlobalEvent();
                    $globalEvent->setName($event['name']);
                    $globalEvent->setSlug($event['slug']);
                    $globalEvent->setStartsAt(new \DateTime($event['starts_at']));
                    $globalEvent->setEndsAt(new \DateTime($event['ends_at']));
                    $globalEvent->setPublished($event['published']);
                    $globalEvent->setCreatedAt(new \DateTime($event['created']));
                    $globalEvent->setUpdatedAt(new \DateTime($event['updated']));
                    $globalEvent->setContent($event['content']);
                    $globalEvent->setTimezone($event['timezone']);
                    $globalEvent->setExternalUrl($event['external_url']);
                    $globalEvent->setDisplayTimezone($event['display_timezone']);
                    $globalEvent->setAddress2('Japan');
                    $globalEvent->setActive($event['ready']);
                    $globalEvent->getSites()->add($siteRepo->find($event['site_id']));
                    $globalEvent->setHostedBy(isset($hosts[$event['slug']]) ? $hosts[$event['slug']] : '');
                    $globalEvent->setLocation(isset($locations[$event['slug']]) ? $locations[$event['slug']] : '');

                    $globalEvent->setApproved(true);
                    $globalEvent->setOnline(false);
                    $globalEvent->setAddress1('');
                    $globalEvent->setRegistrationOption('REGISTRATION_3RDPARTY');
                    $globalEvent->setAttendeeCount(0);
                    $globalEvent->setUser($eventUser);

                    if ($event['bannerImage']) {
                        $banner = new Media();
                        $banner->setFilename('banner/'.$event['bannerImage']);
                        $banner->setCreatedAt(new \DateTime());
                        $banner->setUpdatedAt(new \DateTime());
                        $banner->setMimeType('image/jpeg');
                        $banner->setOwner($eventUser);
                        $banner->setLocale($event['locale'] ?: 'en');

                        $em->persist($banner);
                        $em->flush();

                        $globalEvent->setBannerImage($banner);
                    }

                    $em->persist($globalEvent);
                    $em->flush();
                }

                $output->writeLn('<info>✔</info>');
                $output->writeLn(' - Finished migrating '.$eventCount.' events!');
            }

            $output->writeLn('');
        }

        $output->write('Getting CEVO events...');

        if (($handle = fopen("/home/ubuntu/cevo_events.csv", "r")) !== FALSE) {

            $output->writeLn('<info>✔</info>');
            $output->write(' - Migrating CEVO events...');

            $cevoMigratedCount = 0;

            $sites['demo']   = $siteRepo->find(1);
            $sites['na']     = $siteRepo->find(4);
            $sites['eu']     = $siteRepo->find(5);
            $sites['latam']  = $siteRepo->find(6);
            $sites['in']     = $siteRepo->find(7);
            $sites['mysg']   = $siteRepo->find(8);
            $sites['anz']    = $siteRepo->find(9);

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                $status = strtolower($data[4]);

                if ($alreadyMigrated = $eventService->findOneBy(array('name' => $data[7]))) {
                    continue;
                }

                if ($status == 'active') {
                    $startString            = $data[5];
                    $endString              = $data[6];
                    $name                   = $data[7];
                    $url                    = $data[8];
                    $host                   = $data[11];
                    $enabledSites['na']     = $data[14] == 'X';
                    $enabledSites['eu']     = $data[15] == 'X';
                    $enabledSites['latam']  = $data[16] == 'X';
                    $enabledSites['anz']    = $data[17] == 'X';
                    $enabledSites['mysg']   = $data[18] == 'X';
                    $enabledSites['in']     = $data[19] == 'X';

                    if (substr($url, 0, 1) == '/') {
                        $url = 'http://www.alienwarearena.com'.$url;
                    }

                    $eventSitesArr = array();

                    foreach ($enabledSites as $key => $value) {
                        if ($value) {
                            $eventSitesArr[] = $sites[$key];
                        }
                    }

                    if (count($eventSitesArr) < 1) {
                        $eventSitesArr[] = $sites['demo'];
                    }

                    if ($pos = strpos($endString, 'Set to ') !== false) {
                        $status = strtolower(substr($endString, $pos));
                        continue;
                    }

                    $eventSites = new ArrayCollection($eventSitesArr);

                    $globalEvent = new GlobalEvent();
                    $globalEvent->setName($name);
                    $globalEvent->setStartsAt(new \DateTime($startString));
                    $globalEvent->setEndsAt(new \DateTime($endString));
                    $globalEvent->setPublished(true);
                    $globalEvent->setCreatedAt(new \DateTime());
                    $globalEvent->setUpdatedAt(new \DateTime());
                    $globalEvent->setContent('External Event');
                    $globalEvent->setTimezone('UTC');
                    $globalEvent->setExternalUrl($url);
                    $globalEvent->setDisplayTimezone(true);
                    $globalEvent->setActive($status != 'inactive');
                    $globalEvent->setSites($eventSites);
                    $globalEvent->setHostedBy($host);
                    $globalEvent->setApproved(true);
                    $globalEvent->setOnline(true);
                    $globalEvent->setRegistrationOption('REGISTRATION_3RDPARTY');
                    $globalEvent->setAttendeeCount(0);
                    $globalEvent->setUser($eventUser);

                    $em->persist($globalEvent);
                    $em->flush();

                    $cevoMigratedCount++;
                }
            }

            fclose($handle);
        }

        if ($cevoMigratedCount) {
            $output->writeLn('<info>✔</info>');
            $output->writeLn(' - '.$cevoMigratedCount.' events migrated!');
            $output->writeLn('');
        } else {
            $output->writeLn('<info>✔</info>');
            $output->writeLn(' - No events to migrate!');
            $output->writeLn('');
        }
    }
}
