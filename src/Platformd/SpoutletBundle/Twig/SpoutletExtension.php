<?php

namespace Platformd\SpoutletBundle\Twig;

use Twig_Extension;
use Twig_Filter_Method;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Test_Method;

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

    public function getTests()
    {
        return array(
            'external' => new Twig_Test_Method($this, 'testExternal')
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

    /**
     * Tests whether a URL string (or Linkable object) is an external URL
     *
     * @param $url
     * @return bool
     */
    public function testExternal($url)
    {
        if ($url instanceof LinkableInterface) {
            $url = $this->linkToObject($url);
        }

        if (strpos($url, 'http') === false) {
            return false;
        }

        $currentHost = $this->container->get('request')->getHost();
        if (strpos($url, $currentHost) === false) {
            return true;
        }

        // it has http, but it matches the current host
        return false;
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