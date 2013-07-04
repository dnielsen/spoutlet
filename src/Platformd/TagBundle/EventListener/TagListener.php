<?php

namespace Platformd\TagBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;

class TagListener implements EventSubscriber
{
    protected $manager;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(TagManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @see Doctrine\Common\EventSubscriber
     */
    public function getSubscribedEvents()
    {
        return array(Events::preRemove);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        if (($resource = $args->getEntity()) and $resource instanceof Taggable) {
            $this->manager->deleteTagging($resource);
        }
    }
}
