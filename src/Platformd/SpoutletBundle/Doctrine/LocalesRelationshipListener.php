<?php

namespace Platformd\SpoutletBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Platformd\SpoutletBundle\Entity\GamePage;

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
     * Responsible for taking the gamePageLocales relationship and populating
     * the "faux" locales column with its data after being loaded from the database
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        if ($args->getEntity() instanceof GamePage) {
            /** @var $gamePage \Platformd\SpoutletBundle\Entity\GamePage */
            $gamePage = $args->getEntity();
            // don't run over existing values
            if ($gamePage->areLocalesInitialized()) {
                return;
            }

            $locales = array();
            foreach ($gamePage->getGamePageLocales() as $gamePageLocale) {
                $locales[] = $gamePageLocale->getLocale();
            }

            $gamePage->setLocales($locales);
        }
    }
}