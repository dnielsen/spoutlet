<?php

namespace Platformd\TranslationBundle\Command;

use JMS\TranslationBundle\Translation\ConfigBuilder;
use JMS\TranslationBundle\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use JMS\TranslationBundle\Translation\Config;
use JMS\TranslationBundle\Logger\OutputLogger;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Command for extracting translations.
 *
 * @author Ryan Weaver
 */
class ExtractTranslationToEntitycommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('spoutlet:translations:entity-extract')
            ->setDescription('Updates the translation token entity for any new translations')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->getContainer()->get('jms_translation.config_factory')->getBuilder('entity_extract');

        // change or don't do later
        $builder->setLocale('en');

        /** @var $config \JMS\TranslationBundle\Translation\Config  */
        $config = $builder->getConfig();

        $output->writeln(sprintf('Keep old translations: <info>%s</info>', $config->isKeepOldMessages() ? 'Yes' : 'No'));
        $output->writeln(sprintf('Output-Path: <info>%s</info>', $config->getTranslationsDir()));
        $output->writeln(sprintf('Directories: <info>%s</info>', implode(', ', $config->getScanDirs())));
        $output->writeln(sprintf('Excluded Directories: <info>%s</info>', $config->getExcludedDirs() ? implode(', ', $config->getExcludedDirs()) : '# none #'));
        $output->writeln(sprintf('Excluded Names: <info>%s</info>', $config->getExcludedNames() ? implode(', ', $config->getExcludedNames()) : '# none #'));
        $output->writeln(sprintf('Output-Format: <info>%s</info>', $config->getOutputFormat() ? $config->getOutputFormat() : '# whatever is present, if nothing then '.$config->getDefaultOutputFormat().' #'));
        $output->writeln(sprintf('Custom Extractors: <info>%s</info>', $config->getEnabledExtractors() ? implode(', ', array_keys($config->getEnabledExtractors())) : '# none #'));
        $output->writeln('============================================================');

        $updater = $this->getContainer()->get('pd_translation.translation.updater');

        $logger = new OutputLogger($output)
        if (!$input->getOption('verbose')) {
            $logger->setLevel(OutputLogger::ALL ^ OutputLogger::DEBUG);
        }

        $changeSet = $updater->getChangeSet($config);

        $output->writeln('done!');
    }
}