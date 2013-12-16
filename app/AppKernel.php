<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\DoctrineBundle\DoctrineBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),

            // third part bundles
            new FOS\UserBundle\FOSUserBundle(),
            new JMS\I18nRoutingBundle\JMSI18nRoutingBundle(),
            new EWZ\Bundle\RecaptchaBundle\EWZRecaptchaBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Symfony\Bundle\DoctrineMigrationsBundle\DoctrineMigrationsBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            new Liip\ImagineBundle\LiipImagineBundle(),
            new Liip\ThemeBundle\LiipThemeBundle(),
            new Cybernox\AmazonWebServicesBundle\CybernoxAmazonWebServicesBundle(),
            new Exercise\HTMLPurifierBundle\ExerciseHTMLPurifierBundle(),
            new Vich\GeographicalBundle\VichGeographicalBundle(),

            // KNP bundles
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new Knp\Bundle\MediaExposerBundle\KnpMediaExposerBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Knp\MediaBundle\KnpMediaBundle(),
            new Knp\Bundle\TimeBundle\KnpTimeBundle(),

            // my bundles
            new Platformd\SpoutletBundle\SpoutletBundle(),
            new Platformd\UserBundle\UserBundle(),
            new Platformd\GiveawayBundle\GiveawayBundle(),
            new Platformd\GroupBundle\GroupBundle(),
            new Platformd\NewsBundle\NewsBundle(),
            new Platformd\CEVOBundle\CEVOBundle(),
            new Platformd\SweepstakesBundle\SweepstakesBundle(),
            new Platformd\GameBundle\GameBundle(),

            new Platformd\TranslationBundle\TranslationBundle(),
            new Platformd\MediaBundle\MediaBundle(),
            new Platformd\EventBundle\EventBundle(),
            new Platformd\HtmlWidgetBundle\HtmlWidgetBundle(),
            new Platformd\VideoBundle\VideoBundle(),
            new Platformd\SearchBundle\SearchBundle(),
            new Platformd\TagBundle\TagBundle(),
            new Platformd\IdeaBundle\IdeaBundle(),
            new Platformd\ApiBundle\ApiBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Symfony\Bundle\DoctrineFixturesBundle\DoctrineFixturesBundle();
        }

        if ('test' === $this->getEnvironment()) {
            // don't autoload Symfony2 classes, as they are
            // already loaded by the Symfony2 itself
            if (!defined('BEHAT_AUTOLOAD_SF2')) define('BEHAT_AUTOLOAD_SF2', false);
            require_once 'behat/autoload.php';
            require_once 'mink/autoload.php';

            $bundles[] = new Behat\BehatBundle\BehatBundle();
            $bundles[] = new Behat\MinkBundle\MinkBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');

        if (file_exists($file = __DIR__.'/config/config_'.$this->getEnvironment().'_local.yml')) {
            $loader->load($file);
        }

        if (file_exists($file = __DIR__.'/config/config_server.yml')) {
            $loader->load($file);
        }
    }
}
