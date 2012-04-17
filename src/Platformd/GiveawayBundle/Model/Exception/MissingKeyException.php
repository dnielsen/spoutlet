<?php

namespace Platformd\GiveawayBundle\Model\Exception;

use \InvalidArgumentException;

/**
 * Thrown when we try to assign the user to a giveaway key, but there is none
 */
class MissingKeyException extends InvalidArgumentException
{
    public function __construct($message = 'No giveaway key available to assign', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}