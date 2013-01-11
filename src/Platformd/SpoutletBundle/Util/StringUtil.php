<?php

namespace Platformd\SpoutletBundle\Util;

class StringUtil
{
    /**
     * Returns whether or not the string is null or whitespace
     *
     * @static
     * @param $value
     * @return bool
     */
    static public function IsNullOrEmptyString($value)
    {
        return (!isset($value) || trim($value)==='');
    }
}
