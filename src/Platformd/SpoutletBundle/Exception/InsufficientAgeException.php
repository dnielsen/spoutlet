<?php

namespace Platformd\SpoutletBundle\Exception;

use Exception;

/**
 * Thrown whenever the user is trying to access something and either isn't
 * old enough or we don't have the user's age.
 *
 * Internally, this will cause 1 of 2 things to happen:
 *
 *      a) If we don't know the person's age yet, the user will be show the age page
 *      b) If we *do* know the person's age, then they must not be old enough
 *          and we show them the page with that message
 */
class InsufficientAgeException extends Exception
{
}