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
 * Command that places themes assets into a given directory.
 */
class ThemesInstallCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('themes:install')
            ->setDefinition(array(
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
            ))
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the themes assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
            ->setDescription('Installs themes web assets under a public web directory')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command installs themes assets into a given
directory (e.g. the web directory).

<info>php %command.full_name% web</info>

A "themes" directory will be created inside the target directory, and the
"public" directory of each bundle in each theme will be copied under a
directory named after the theme name into this "themes" directory.

To create a symlink to each bundle instead of copying its assets, use the
<info>--symlink</info> option:

<info>php %command.full_name% web --symlink</info>

To make symlink relative, add the <info>--relative</info> option:

<info>php %command.full_name% web --symlink --relative</info>

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

        $targetArg = rtrim($input->getArgument('target'), '/');

        if (!is_dir($targetArg)) {
            throw new \InvalidArgumentException(sprintf('The target directory "%s" does not exist.', $input->getArgument('target')));
        }

        if (!function_exists('symlink') && $input->getOption('symlink')) {
            throw new \InvalidArgumentException('The symlink() function is not available on your system. You need to install the assets without the --symlink option.');
        }

        $filesystem = $this->getContainer()->get('filesystem');

        // Create the themes directory otherwise symlink will fail.
        $publicThemesDir = $targetArg.'/themes/';
        $filesystem->mkdir($publicThemesDir, 0777);

        $output->writeln(sprintf("Installing themes using the <comment>%s</comment> option", $input->getOption('symlink') ? 'symlink' : 'hard copy'));

        $themesDir = $this->getContainer()->getParameter('kernel.root_dir') . '/Resources/themes';
        foreach ($themes as $theme) {
            if (is_dir($themeDir = $themesDir.'/'.$theme)) {
                $iterator = Finder::create()->directories()->name('*Bundle')->in($themeDir);
                $targetDir = $publicThemesDir.$theme;

                $output->writeln(sprintf('Installing <comment>%s</comment> themes assets into <comment>%s</comment>', $theme, $targetDir));

                foreach ($iterator as $bundleDir) {
                    if (is_dir($originDir = $bundleDir->getRealPath().'/public')) {
                        $targetDir  = $publicThemesDir.$theme.'/'.preg_replace('/bundle$/', '', strtolower($bundleDir->getFileName()));
                        $filesystem->remove($targetDir);

                        // Create the specific theme directory otherwise symlink will fail.
                        $specificThemeDir = dirname($targetDir);
                        $filesystem->mkdir($specificThemeDir, 0777);

                        if ($input->getOption('symlink')) {
                            if ($input->getOption('relative')) {
                                $relativeOriginDir = $filesystem->makePathRelative($originDir, realpath($publicThemesDir));
                            } else {
                                $relativeOriginDir = $originDir;
                            }

                            $filesystem->symlink($relativeOriginDir, $targetDir);
                        } else {
                            $filesystem->mkdir($targetDir, 0777);
                            // We use a custom iterator to ignore VCS files
                            $filesystem->mirror($originDir, $targetDir, Finder::create()->in($originDir));
                        }
                    }
                }

                $assetsTemplateFile = $theme . '_assets.html.twig';
                $output->writeln(sprintf("Installing assets template file <comment>%s</comment> in Assetic exposed directory", $assetsTemplateFile));

                $viewDir = $this->getContainer()->getParameter('kernel.root_dir') . '/Resources/views';
                // Create the views directory otherwise symlink will fail.
                $filesystem->mkdir($viewDir, 0777);

                $originAssetsTemplateFile = $themeDir . DIRECTORY_SEPARATOR . $assetsTemplateFile;
                $targetAssetsTemplateFile = $viewDir . DIRECTORY_SEPARATOR . $assetsTemplateFile;

                $filesystem->symlink($originAssetsTemplateFile, $targetAssetsTemplateFile);
            }
        }
    }
}
