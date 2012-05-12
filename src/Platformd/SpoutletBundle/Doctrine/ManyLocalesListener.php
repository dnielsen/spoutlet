<?php

namespace Platformd\SpoutletBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Platformd\SpoutletBundle\Entity\GamePage;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\GamePageLocale;

/**
 * Transforms a "locales" array property to a proper "hasMany" relationship
 *
 * This helps us overcome the fact that "locales" aren't a database object,
 * so creating a classic ManyToMany to it actually involves creating a OneToMany
 * to a new entity. Getting that in a form is hard, unless we can just set
 * a locales array and have a listener handle the relationship.
 *
 * This does exactly that.
 */
class ManyLocalesListener implements EventSubscriber
{
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
        if ($args->getEntity() instanceof GamePage) {
            $this->handleEvent($args->getEntity(), $args->getEntityManager());
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        if ($args->getEntity() instanceof GamePage) {
            $this->handleEvent($args->getEntity(), $args->getEntityManager());

            // hack to recompute the changeset for update
            $this->recomputeChangeset($args->getEntity(), $args->getEntityManager());
        }
    }

    private function handleEvent(GamePage $gamePage, EntityManager $em)
    {
        $remainingNewLocales = $gamePage->getLocales();

        foreach ($gamePage->getGamePageLocales() as $gamePageLocale) {
            if (in_array($gamePageLocale->getLocale(), $remainingNewLocales)) {
                // no need to remove this GamePageLocale
                // but let's make sure it doesn't get added new
                $key = array_search($gamePageLocale->getLocale(), $remainingNewLocales);
                unset($remainingNewLocales[$key]);
            } else {
                // this GamePageLocale is no longer being used... murder it!
                $em->remove($gamePageLocale);
                // update the inverse side for good measure
                $gamePage->getGamePageLocales()->removeElement($gamePageLocale);

                $this->recomputeChangeset($gamePageLocale, $em);
            }
        }

        // now add the new guys
        foreach ($remainingNewLocales as $newLocale) {
            $newGamePageLocale = new GamePageLocale();
            $newGamePageLocale->setGamePage($gamePage);
            $newGamePageLocale->setLocale($newLocale);
            $em->persist($newGamePageLocale);

            $this->recomputeChangeset($newGamePageLocale, $em);

            // set the inverse side
            $gamePage->getGamePageLocales()->add($newGamePageLocale);
        }
    }

    /**
     * Forces Doctrine to "notice" the changes we've made to an entity
     *
     * @param $entity
     * @param \Doctrine\ORM\EntityManager $em
     */
    private function recomputeChangeset($entity, EntityManager $em)
    {
        $classMetadata = $em->getClassMetadata(get_class($entity));
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($classMetadata, $entity);
    }
}