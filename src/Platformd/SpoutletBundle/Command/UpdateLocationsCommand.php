<?php

namespace Platformd\SpoutletBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use DateTime;

use Platformd\SpoutletBundle\Entity\Location;

class UpdateLocationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pd:locations:update')
            ->setDescription('Forces an update of the coordinates of all location entities')
            ->setHelp(<<<EOT
The <info>pd:locations:update</info> command forces the coordinates of all location entities to be requeried from Google Maps:

  <info>php app/console pd:locations:update</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container      = $this->getContainer();
        $em             = $container->get('doctrine')->getEntityManager();
        $repo           = $em->getRepository('SpoutletBundle:Location');

        $output->writeLn('');
        $output->write('Getting locations...');

        $locations = $repo->findAll();
        $locationCount = count($locations);

        if ($locationCount < 1) {
            $output->writeLn('<comment>✔</comment>');
            $output->writeLn(' - No locations found!');
            $output->writeLn('');
            exit;
        }

        $output->writeLn('<info>✔</info>');
        $output->write(' - '.$locationCount.' locations found. Updating...');

        foreach ($locations as $location) {

            // Add blank space to address1 and flush to force Vich to requery
            $location->setAddress1($location->getAddress1().' ');
            $em->persist($location);
            $em->flush();

            // Reset address to original
            $location->setAddress1(trim($location->getAddress1()));
            $em->persist($location);
        }

        // Process any remaining changes
        $em->flush();

        $output->writeLn('<info>✔</info>');
        $output->writeLn(' - '.$locationCount.' locations updated.');
        $output->writeLn('');
    }
}
