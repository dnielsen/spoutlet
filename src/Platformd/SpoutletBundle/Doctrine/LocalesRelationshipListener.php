<?php

namespace Platformd\SpoutletBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Platformd\SpoutletBundle\Locale\JoinedLocaleInterface;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipInterface;

class LocalesRelationshipListener implements EventSubscriber
{
    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postLoad,
        );
    }

    /**
     * Responsible for taking the "joined locales" relationship of some entity
     * that implements LocalesRelationshipInterface and populating
     * the "faux" locales column with its data after being loaded from the
     * database
     *
     * For example, GamePage HasMany GamePageLocales on a relationship
     * under GamePage.gamePageLocales. This would guarantee that the
     * GamePage.locales property contains an array of the locale names
     * whenever a GamePage is loaded from the database.
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        if ($args->getEntity() instanceof LocalesRelationshipInterface) {
            /** @var $entityWithLocales \Platformd\SpoutletBundle\Locale\LocalesRelationshipInterface */
            $entityWithLocales = $args->getEntity();
            // don't run over existing values
            if ($entityWithLocales->areLocalesInitialized()) {
                return;
            }

            $locales = array();
            /** @var $joinedLocale \Platformd\SpoutletBundle\Locale\JoinedLocaleInterface */
            foreach ($entityWithLocales->getJoinedLocales() as $joinedLocale) {
                $locales[] = $joinedLocale->getLocale();
            }

            $entityWithLocales->setLocales($locales);
        }
    }
}