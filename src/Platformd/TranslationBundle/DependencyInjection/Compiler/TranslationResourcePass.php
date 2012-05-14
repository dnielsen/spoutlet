<?php

namespace Platformd\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds our "entity" translation resources at the end of all other resources
 *
 * This has 2 effects:
 *  a) The entity loader for translations is used
 *  b) The entity loader is used after all other loaders, giving it priority
 */
class TranslationResourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $translator = $container->findDefinition('translator');

        $locales = $container->getParameter('available_locales');
        $domains = $container->getParameter('available_translation_domains');

        // add a resource for every locale and every domain
        foreach ($locales as $locale) {
            foreach ($domains as $domain) {
                $translator->addMethodCall('addResource', array('entity', 'file_resource_not_used', $locale, $domain));
            }
        }
    }
}