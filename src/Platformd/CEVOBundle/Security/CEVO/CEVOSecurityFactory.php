<?php

namespace Platformd\CEVOBundle\Security\CEVO;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates services for API token authentication
 */
class CEVOSecurityFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $provider = 'cevo.authentication.provider.cevo.' . $id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('cevo.authentication.provider.cevo'))
        ;

        $listener = 'cevo.authentication.listener.cevo.' . $id;
        $container
            ->setDefinition($listener, new DefinitionDecorator('cevo.authentication.listener.cevo'))
        ;

        if ($defaultEntryPoint === null) {
            $defaultEntryPoint = $this->createEntryPoint($container, $id, $config, $defaultEntryPoint);
        }

        return array($provider, $listener, $defaultEntryPoint);
    }

    /**
     * {@inheritDoc}
     *
     * @todo not sure this is the good position
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return 'cevo-auth';
    }

    /**
     * Creates the entry point that redirects to CEVO on login
     *
     * @param $container
     * @param $id
     * @param $config
     * @param $defaultEntryPoint
     * @return string
     */
    protected function createEntryPoint(ContainerInterface $container, $id, $config, $defaultEntryPoint)
    {
        $entryPointId = 'cevo.authentication.entry_point.cevo.'.$id;
        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('cevo.authentication.entry_point.cevo'))
        ;

        return $entryPointId;
    }

    public function addConfiguration(NodeDefinition $builder)
    {
    }


}