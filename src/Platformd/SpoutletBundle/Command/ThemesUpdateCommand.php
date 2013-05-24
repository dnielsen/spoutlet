<?php

namespace Platformd\SpoutletBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Finder\Finder;

/**
 * Command that updates the git submodules for themes
 */
class ThemesUpdateCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('themes:update')
            ->setDescription('Performs git submodule commands for initializing and updating themes')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command initializes and updates git submodules, which are used for themes.

<info>php %command.full_name%</info>

EOT
            )
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! ($themes = $this->getContainer()->getParameter('liip_theme.themes'))) {
            throw new \HttpRuntimeException('No themes were found.');
        }

        echo `git submodule update --init`;
        echo "\n";
        echo `git submodule foreach git checkout master`;
        echo "\n";
        echo `git submodule foreach git pull origin master`;
        echo "\n";
    }
}
