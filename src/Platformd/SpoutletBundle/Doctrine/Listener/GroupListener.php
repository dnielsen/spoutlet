<?php

namespace Platformd\SpoutletBundle\Doctrine\Listener;

use Platformd\GroupBundle\Entity\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Events,
    Doctrine\Common\Collections\ArrayCollection
;

class GroupListener  implements EventSubscriber
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $em            = $eventArgs->getEntityManager();
        $entity        = $eventArgs->getEntity();

        if (!$entity instanceof Group) {
            return;
        }

        if ($entity->getAllLocales()) {
            $siteArr = $this->container->get('doctrine.orm.entity_manager')->getRepository('SpoutletBundle:Site')->findAll();

            $entity->setSites(new ArrayCollection());

            foreach ($siteArr as $site) {
                $entity->getSites()->add($site);
            }
        }
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
