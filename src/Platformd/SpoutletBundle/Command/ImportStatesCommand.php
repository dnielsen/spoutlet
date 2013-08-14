<?php

namespace Platformd\SpoutletBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Platformd\SpoutletBundle\Entity\CountryState;

class ImportStatesCommand extends ContainerAwareCommand
{
    private $stdOutput;

    protected function configure()
    {
        $this
            ->setName('pd:states:import')
            ->addArgument('path', InputArgument::REQUIRED, 'CSV file containing country code to state mapping.')
            ->setDescription('Imports state/province information from a CSV mapping file.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command uses a .csv mapping file to insert states/provinces and link them to countries:

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container       = $this->getContainer();
        $em              = $container->get('doctrine')->getEntityManager();
        $path            = $input->getArgument('path');
        $this->stdOutput = $output;
        $countryRepo     = $em->getRepository('SpoutletBundle:Country');
        $stateRepo       = $em->getRepository('SpoutletBundle:CountryState');

        $countries = array();
        $states    = array();

        $this->output();
        $this->output(0, 'Country-State/Province Import Script');

        $this->output(2, 'Getting codes for countries...', false);

        $countryList = $countryRepo->findAll();

        foreach ($countryList as $country) {
            $countries[$country->getCode()] = $country;
        }

        $country = null;

        $stateList = $stateRepo->findAll();

        foreach ($stateList as $state) {
            $states[$state->getName()] = $state;
        }

        $state = null;

        $this->tick();

        $this->output(2, 'Importing states...');
        $this->output();

        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $countryCode    = $data[0] == 'GB' ? 'UK' : $data[0];
                $stateName      = ucwords(strtolower($data[1]));

                $this->output(4, 'State => { CountryCode = "'.$countryCode.'", State = "'.$stateName.'" }');

                $country = isset($countries[$countryCode]) ? $countries[$countryCode] : null;

                if (!$country) {
                    $this->output(6, 'Country "'.$countryCode.'" not found, skipping');
                    continue;
                }

                $state = isset($states[$stateName]) ? $states[$stateName] : null;

                if ($state) {
                    $this->output(6, 'State "'.$stateName.'" already imported, skipping');
                    continue;
                }

                $state = new CountryState();

                $state->setName($stateName);
                $state->setCountry($country);

                $em->persist($state);
            }

            $em->flush();
        }

        $this->output();
        $this->output(2, 'State import complete.');
        $this->output();
    }
}
