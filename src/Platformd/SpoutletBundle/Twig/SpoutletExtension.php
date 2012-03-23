<?php

namespace Platformd\SpoutletBundle\Twig;

use Twig_Extension;
use Twig_Filter_Method;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic Twig extension for the project
 */
class SpoutletExtension extends Twig_Extension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function getFilters()
    {
        return array(
            'pd_link' => new Twig_Filter_Method($this, 'linkToObject'),
        );
    }

    /**
     * @param $obj
     * @return string
     */
    public function linkToObject($obj)
    {
        if (!$obj instanceof LinkableInterface) {
            throw new \InvalidArgumentException('You must pass an object that implements LinkableInterface to the pd_link filter.');
        }

        return $this->getLinkableManager()->link($obj);
    }

    public function getName()
    {
        return 'spoutlet';
    }

    /**
     * @return \Platformd\SpoutletBundle\Link\LinkableManager
     */
    private function getLinkableManager()
    {
        return $this->container->get('platformd.link.linkable_manager');
    }
}