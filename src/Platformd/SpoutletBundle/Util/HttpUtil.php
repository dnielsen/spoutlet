<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\HttpFoundation\Request;

class HttpUtil
{
    /**
     * Returns whether or not the URL is external per the current host
     *
     * @static
     * @param $url
     * @param $currentHost
     * @return bool
     */
    static public function isUrlExternal($url, $currentHost)
    {
        if (strpos($url, 'http') === false) {
            return false;
        }

        if (strpos($url, $currentHost) === false) {
            return true;
        }

        // it has http, but it matches the current host
        return false;
    }
}