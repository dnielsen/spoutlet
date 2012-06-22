<?php

namespace Platformd\SpoutletBundle\Locale;

/**
 * An interface put on any main entity (e.g. GamePage) that "hasMany" locales/sites
 *
 * This is important because it's used by the LocalesRelationshipListener
 * to ensure that "setLocales" is called on the entity when we query for it.
 *
 * Basically, until "locales" are replaced with a Site entity, we have some
 * extra work to do when we related an entity to many locales;
 */
interface LocalesRelationshipInterface
{
    /**
     * Returns true if setLocales as already been called and the locales are set
     *
     * e.g.
     *      return is_array($this->locales);
     *
     * @abstract
     * @return boolean
     */
    function areLocalesInitialized();

    /**
     * Returns the ArrayCollection of
     *
     * @abstract
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    function getJoinedLocales();

    function setLocales(array $locales);
}