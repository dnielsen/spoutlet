<?php

namespace Platformd\EventBundle\Doctrine\listener;

use Platformd\EventBundle\Entity\GroupEvent,
    Platformd\SpoutletBundle\Util\SiteUtil
;

use Symfony\Component\DependencyInjection\Container;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Events
;

class GroupEventListener implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $em            = $eventArgs->getEntityManager();
        $entity        = $eventArgs->getEntity();

        if (!$entity instanceof GroupEvent) {
            return;
        }

        if ($locale = $this->getCurrentLocale()) {
            $entity->setCurrentLocale($locale);
        }
    }


    private function getCurrentLocale()
    {
        return $this->container->get('platformd.util.site_util')->getCurrentSite();
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postLoad,
        );
    }
}
