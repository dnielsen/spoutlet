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

    /**
     * Sets the locales on the object
     *
     * @abstract
     * @param array $locales
     */
    function setLocales(array $locales);

    /**
     * Returns the locales that were set on setLocales
     *
     * @abstract
     * @return array
     */
    function getLocales();

    /**
     * A funny function where you create a new Instance of whatever the
     * entities actual JoinedLocaleInterface is. You'll typically also
     * need to set the relationship on that new object back to this object:
     *
     *     $newGamePageLocale = new GamePageLocale();
     *     $newGamePageLocale->setGamePage($this);
     *
     *     return $newGamePageLocale;
     *
     * @abstract
     * @return \Platformd\SpoutletBundle\Locale\JoinedLocaleInterface
     */
    function createJoinedLocale();
}