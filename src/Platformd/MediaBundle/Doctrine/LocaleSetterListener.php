<?php

namespace Platformd\MediaBundle\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Platformd\MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets the locale on the Media
 */
class LocaleSetterListener
{
    private $container;

    /**
     * Injecting the whole container so we don't hit request scope issues
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Media && !$entity->getLocale()) {
            $entity->setLocale($this->getLocale());
        }
    }

    /**
     * @return string
     */
    private function getLocale()
    {
        /** @var $request \Symfony\Component\HttpFoundation\Request */
        $request = $this->container->get('request');

        // offers forwards compatability with Symfony 2.1
        if (method_exists($request, 'getLocale')) {
            return $request->getLocale();
        } else {
            return $request->getSession()->getLocale();
        }
    }
}