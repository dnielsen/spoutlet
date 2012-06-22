<?php

namespace Platformd\SpoutletBundle\Locale;

/**
 * The interface for any faux "join" table (e.g. GamePageLocale) that represents
 * a relationship between an entity and its many locales
 */
interface JoinedLocaleInterface
{
    /**
     * Returns true if setLocales as already been called and the locales are set
     *
     * e.g.
     *      return is_array($this->locales);
     *
     * @abstract
     * @return string
     */
    function getLocale();
}