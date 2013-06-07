<?php

namespace Platformd\SpoutletBundle\Link;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Responsible for creating links to objects that implement LinkableInterface
 */
class LinkableManager
{
    /**
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Creates the URL to a "linkable" object:
     *
     *      a) If the "linkableOverrideUrl" is present it wins
     *      b) Else, a URL is generated from the route name and params
     *
     * @param LinkableInterface $linkableObject
     * @return string
     */
    public function link(LinkableInterface $linkableObject, $absolute=false)
    {
        if ($linkableObject->getLinkableOverrideUrl()) {
            return $linkableObject->getLinkableOverrideUrl();
        }

        $route = $linkableObject->getLinkableRouteName();
        $params = $linkableObject->getLinkableRouteParameters();

        return $this->router->generate($route, $params, $absolute);
    }
}
