<?php

namespace Platformd\SpoutletBundle\Twig;

use Twig_Extension;
use Twig_Filter_Method;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

/**
 * Twig extension for generic things
 */
class SpoutletExtension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            'site_name' => new Twig_Filter_Method($this, 'translateSiteName')
        );
    }

    /**
     * Translates a site "key" (en) into a site name (Demo)
     *
     * @param $key
     * @return string
     */
    public function translateSiteName($key)
    {
        return MultitenancyManager::getSiteName($key);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'spoutlet';
    }
}