<?php

namespace Platformd\TranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Platformd\TranslationBundle\DependencyInjection\Compiler\TranslationResourcePass;

class TranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslationResourcePass());
    }
}