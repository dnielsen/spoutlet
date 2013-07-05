<?php

namespace Platformd\SearchBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Platformd\SearchBundle\Model\IndexableInterface;
use Platformd\TagBundle\Entity\Tagging;

use Monolog\Logger;

class SearchIndexListener implements EventSubscriber
{
    private $container;

    function __construct($container)
    {
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::postUpdate,
            Events::postPersist,
            Events::preRemove,
        );
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
       $this->index($args, true);
    }

    private function index(LifecycleEventArgs $args, $remove = false)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();
        $searchManager = $this->container->get('platformd.model.search_manager');
        $tagManager = $this->container->get('platformd.tags.model.tag_manager');

        if ($entity instanceof IndexableInterface) {
            $searchManager->indexEntity($entity, $remove);
        }

        if ($entity instanceof Tagging) {
            $lookupClass = $tagManager->getClassFromResource($entity->getResourceType());
            $repo        = $em->getRepository($lookupClass);

            if ($repo) {
                $indexEntity = $repo->find($entity->getResourceId());

                if ($indexEntity instanceof IndexableInterface) {
                    $searchManager->indexEntity($indexEntity);
                }
            }
        }
    }

    private function log($msg, $level = Logger::DEBUG)
    {
        if ($this->logger) {
            $this->logger->addRecord($level, $msg);
        }
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
}
