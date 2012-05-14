<?php

namespace Platformd\SpoutletBundle\Link;

/**
 * Interface that makes an object "linkable"
 *
 * This is used to allow us to pass objects to a Twig extension and have
 * it figure out how to create a link to that object.
 */
interface LinkableInterface
{
    /**
     * If there is a set URL that should be used without doing anything else, return it here
     *
     * @abstract
     * @return string
     */
    function getLinkableOverrideUrl();

    /**
     * Returns the name of the route used to link to this object
     *
     * @abstract
     * @return string
     */
    function getLinkableRouteName();

    /**
     * Returns an array route parameters to link to this object
     *
     * @abstract
     * @return array
     */
    function getLinkableRouteParameters();
}