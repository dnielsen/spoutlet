<?php

namespace Platformd\GiveawayBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

class AssignedKeyCountryAllocationCommand extends ContainerAwareCommand
{
    private $stdOutput;

    protected function configure()
    {
        $this
            ->setName('pd:assignedKeys:assignCountries')
            ->setDescription('Assigns IP address-based countries to assigned giveaway and deal keys')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command looks up the stored IP address for allocated keys and adds the country they are from to the database row.

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();
        $dealCodeRepo        = $em->getRepository('GiveawayBundle:DealCode');
        $giveawayKeyRepo     = $em->getRepository('GiveawayBundle:GiveawayKey');
        $countryRepo         = $em->getRepository('SpoutletBundle:Country');
        $ipLookupUtil        = $container->get('platformd.model.ip_lookup_util');

        $this->output(0);
        $this->output(0, 'PlatformD Assigned Key Country Processor');
        $this->output(0);

        $this->output(0, 'Getting all assigned keys...', false);

        $assignedDealCodes    = $dealCodeRepo->getAllAssignedKeysWithoutCountry();
        $assignedGiveawayKeys = $giveawayKeyRepo->getAllAssignedKeysWithoutCountry();

        $this->tick();

        $this->output(2, 'Assigning countries to deal codes...');

        foreach ($assignedDealCodes as $code) {
            $countryCode = $ipLookupUtil->getCountryCode($code->getIpAddress());

            if ($countryCode) {
                $country = $countryRepo->findOneByCode($countryCode);

                if ($country) {
                    $code->setCountry($country);
                    $em->persist($country);
                    $em->flush();
                }
            }
        }

        $this->output(2, 'Assigning countries to giveaway keys...');

        foreach ($assignedGiveawayKeys as $code) {
            $countryCode = $ipLookupUtil->getCountryCode($code->getIpAddress());

            if ($countryCode) {
                $country = $countryRepo->findOneByCode($countryCode);

                if ($country) {
                    $code->setCountry($country);
                    $em->persist($country);
                    $em->flush();
                }
            }
        }

        $this->output();
        $this->output(2, 'No more assigned keys.');

        $this->output(0);
    }
}
