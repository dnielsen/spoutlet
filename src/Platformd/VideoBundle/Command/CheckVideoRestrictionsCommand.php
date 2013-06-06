<?php

namespace Platformd\VideoBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Doctrine\Common\Collections\ArrayCollection;

use
    Platformd\SpoutletBundle\Entity\Gallery,
    Platformd\VideoBundle\Entity\YoutubeVideo
;

class CheckVideoRestrictionsCommand extends ContainerAwareCommand
{
    private $stdOutput;
    
    const DELAY_BETWEEN_CHECKS_MILLISECONDS = 500;

    protected function configure()
    {
        $this
            ->setName('pd:videos:updateRestrictions')
            ->setDescription('Checks youtube videos for new restrictions and updates the database accordingly.')
            ->setHelp(<<<EOT
The <info>pd:videos:updateRestrictions</info> command checks each video for updates to its restrictions, storing any changes in the database for lookup:

  <info>php app/console pd:videos:updateRestrictions</info>
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
        $container      = $this->getContainer();
        $em             = $container->get('doctrine')->getEntityManager();
        $videoRepo      = $em->getRepository('VideoBundle:YoutubeVideo');
        $countryRepo    = $em->getRepository('SpoutletBundle:Country');
        $youtubeManager = $container->get('platformd.model.youtube_manager');

        $this->stdOutput = $output;

        $this->output(0);
        $this->output(0, 'Youtube Video Restrictions Update Script');

        $this->output(2, 'Getting videos...', false);
        $videos = $videoRepo->findAllSortedByCheckDate();
        $this->tick();

        $this->output(2, 'Getting country information...', false);
        $countries = $countries  = $countryRepo->findAll();

        $countryArray = array();

        foreach ($countries as $country) {
            $countryArray[$country->getCode()] = $country;
        }

        $this->tick();

        $this->output(2, 'Updating videos...');
        $this->output(0);

        foreach ($videos as $video) {
        
            usleep(self::DELAY_BETWEEN_CHECKS_MILLISECONDS);

            $this->output(4, 'Updating YouTube ID [ '.$video->getYoutubeId().' ]');

            $restrictionsAndErrors  = $youtubeManager->getRestrictionsAndErrors($video->getYoutubeId());
            $youtubeRestrictions    = $restrictionsAndErrors['restrictions'];
            $youtubeErrors          = $restrictionsAndErrors['errors'];

            $videoCountries = array();

            if(isset($youtubeRestrictions['type']) && isset($youtubeRestrictions['relationship'])) {

                if($youtubeRestrictions['type'] == 'country') {

                    $youtubeCountries = explode(" ", $youtubeRestrictions['countries']);

                    $restrictionType = ($youtubeRestrictions['relationship'] == 'allow');
                    $this->output(6, 'Setting restrictions for type "'.$youtubeRestrictions['relationship'].'"...', false);

                    $video->setRestrictionType($restrictionType);

                    $videoCountries = array();

                    foreach ($youtubeCountries as $code) {

                        $code = $code == 'GB' ? 'UK' : $code;

                        if (isset($countryArray[$code])) {
                            $videoCountries[] = $countryArray[$code];
                        }
                    }

                    $video->setRestrictionCountries($videoCountries);

                    $this->tick();
                }
            } else {
                $video->setRestrictionCountries($videoCountries);
                $this->output(6, 'No restrictions set');
            }

            if(isset($youtubeErrors['code']) && isset($youtubeErrors['errors'])) {

                if ($youtubeErrors['code'] == 403) {
                    $this->output(6, 'Video inaccessible - updating information...', false);
                    $video->setIsAccessible(false);
                    $this->tick();
                    $this->output(0);
                }

            } else {
                $video->setIsAccessible(true);
                $this->output(6, 'No errors received');
                $this->output(0);
            }

            $video->setRestrictionsChecked(new \DateTime());

            $em->persist($video);
            $em->flush();
        }
    }
}
