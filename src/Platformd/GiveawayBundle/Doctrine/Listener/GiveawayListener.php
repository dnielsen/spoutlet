<?php

namespace Platformd\GiveawayBundle\Doctrine\Listener;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Symfony\Component\DependencyInjection\Container;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Events
;

class GiveawayListener  implements EventSubscriber
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container; // circulaer reference if not
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $em            = $eventArgs->getEntityManager();
        $entity        = $eventArgs->getEntity();

        if (!$entity instanceof Giveaway) {
            return;
        }

        if ($locale = $this->getCurrentLocale()) {
            $entity->setCurrentLocale($locale);
        }
    }

    private function getCurrentLocale()
    {
        return $this->container->get('platformd.model.site_util')->getCurrentSite();
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

