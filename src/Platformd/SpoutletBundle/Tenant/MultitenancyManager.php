<?php

namespace Platformd\SpoutletBundle\Tenant;

/**
 * Manager for multitenancy
 */
class MultitenancyManager
{
    static private $sites = array(
        'en' => 'Demo',
        'ja' => 'Japan',
        'zh' => 'China',
    );

    static public function getSiteChoices()
    {
        return self::$sites;
    }

    /**
     * @param $key
     * @return string
     */
    static public function getSiteName($key)
    {
        return isset(self::$sites[$key]) ? self::$sites[$key] : null;
    }
}