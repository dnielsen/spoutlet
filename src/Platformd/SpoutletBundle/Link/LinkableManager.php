<?php

namespace Platformd\SpoutletBundle\Link;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Platformd\GroupBundle\Entity\Group;
use Platformd\EventBundle\Entity\Event;
use Platformd\IdeaBundle\Entity\EventSession;

/**
 * Responsible for creating links to objects that implement LinkableInterface
 */
class LinkableManager
{
    /**
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    private $router;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function __construct(UrlGeneratorInterface $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->container = $container;
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

        $currentSite = $this->container->get('platformd.util.site_util')->getCurrentSite();

        if ($currentSite->getCommunityGroup()) {

            $group = $this->getGroup($linkableObject);

            $isTopParent = true;
            $topParent = $group;
            $slugVariableName = 'groupSlug';
            while($topParent->getParent() != null) {
                $isTopParent = false;
                $topParent = $topParent->getParent();
            }

            if ($this->isGroup($linkableObject) ) {
                if ($isTopParent) {
                    return $this->router->generate('default_index', array(), $absolute);
                }
                $slugVariableName = 'slug';
            }

            if ($topParent->getCommunitySite()) {
                if ($slug = $group->getRelativeSlug()) {
                    $params[$slugVariableName] = $slug;
                }
            }
        }
        return $this->router->generate($route, $params, $absolute);
    }

    private function isGroup($linkable) {
        return $linkable instanceof Group;
    }

    private function getGroup($linkableObject) {
        if ($linkableObject instanceof Group)
            return $linkableObject;
        else if( $linkableObject instanceof Event )
            return $linkableObject->getGroup();
        else if( $linkableObject instanceof EventSession )
            return $linkableObject->getEvent()->Group();
        return null;
    }
}
