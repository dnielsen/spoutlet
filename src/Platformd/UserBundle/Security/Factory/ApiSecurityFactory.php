<?php

namespace Platformd\Userbundle\Security\Factory;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;

class ApiSecurityFactory extends FormLoginFactory
{
    public function getKey()
    {
        return 'api-auth';
    }

    protected function getListenerId()
    {
        return 'security.authentication.listener.form';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'platformd.security.user.authentication.provider.api.'.$id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('platformd.security.user.authentication.provider.api'))
            ->replaceArgument(3, $id)
        ;

        return $provider;
    }

    public function addConfiguration(NodeDefinition $node)
    {
        parent::addConfiguration($node);

        $node
            ->children()
            ->scalarNode('api_authentication')->defaultValue(true)
            ->end();
    }
}
