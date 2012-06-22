<?php

namespace Platformd\SpoutletBundle\Locale;

use Doctrine\ORM\EntityManager;

/**
 * Utility class for some things related to the LocalesRelationshipInterface behavior
 */
class LocalesRelationshipHelper
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * This function should be called before persisting any entity that implements LocalesRelationshipInterface
     *
     * Unfortunately, this cannot be done via a listener in Doctrine, as
     * the preUpdate listener is not powerful enough to handle inserts and removes :/
     *
     * @param LocalesRelationshipInterface $entityWithLocales
     */
    public function processLocalesSave(LocalesRelationshipInterface $entityWithLocales)
    {
        $remainingNewLocales = $entityWithLocales->getLocales();

        /** @var $joinedLocale \Platformd\SpoutletBundle\Entity\Superclass\JoinedLocale */
        foreach ($entityWithLocales->getJoinedLocales() as $joinedLocale) {
            if (in_array($joinedLocale->getLocale(), $remainingNewLocales)) {
                // no need to remove this JoinedLocale
                // but let's make sure it doesn't get added new
                $key = array_search($joinedLocale->getLocale(), $remainingNewLocales);
                unset($remainingNewLocales[$key]);
            } else {
                // this JoinedLocale is no longer being used... murder it!
                $this->em->remove($joinedLocale);
                // update the inverse side for good measure
                $entityWithLocales->getJoinedLocales()->removeElement($joinedLocale);
            }
        }

        // now add the new guys
        foreach ($remainingNewLocales as $newLocale) {
            $newJoinedLocale = $entityWithLocales->createJoinedLocale();
            $newJoinedLocale->setLocale($newLocale);
            $this->em->persist($newJoinedLocale);

            // set the inverse side
            $entityWithLocales->getJoinedLocales()->add($newJoinedLocale);
        }
    }
}