<?php

namespace Platformd\TranslationBundle\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Platformd\TranslationBundle\Entity\Translation;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Symfony\Component\Finder\Finder;

class TranslationListener implements EventSubscriber
{
    private $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::preUpdate,
        );
    }


    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Translation) {
            $this->clearTranslationCache();
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Translation) {
            $this->clearTranslationCache();
        }
    }

    /**
     * Clears the translation cache in the current environment
     *
     * @todo - This is a hack, but there's no other way
     */
    private function clearTranslationCache()
    {
        $finder = new Finder();

        // clear translations in all environments
        $finder->in(array($this->cacheDir))
            ->name('translations')
            ->directories()
        ;

        foreach($finder as $translationDirectory){
            $subFinder = new Finder();
            $subFinder->in($translationDirectory->getRealpath())
                ->files()
            ;

            foreach ($subFinder as $file) {
                unlink($file->getRealpath());
            }
        }
    }
}