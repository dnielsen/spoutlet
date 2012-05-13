<?php

namespace Platformd\SpoutletBundle\Tenant;

/**
 * Manager for multitenancy
 */
class MultitenancyManager
{
    /**
     * @todo - sites is repeated as DIC parameters
     *
     * @var array
     */
    static private $sites = array(
        'en'    => 'Demo',
        'ja'    => 'Japan',
        'zh'    => 'China',
        'en_AU' => 'Australia / New Zealand',
        'en_GB' => 'Europe',
        'en_IN' => 'India',
        'es'    => 'Latin America',
        'en_SG' => 'Singapore',
        'en_US' => 'USA',
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