<?php

namespace Platformd\EventBundle\Map;

use Vich\GeographicalBundle\Map\Map;

/**
 * LargeLocationMap.
 */
class LargeLocationMap extends Map
{
    /**
     * Constructs a new instance of LargeLocationMap.
     */
    public function __construct()
    {
        parent::__construct();

        // configure your map in the constructor
        // by setting the options

        $this->setAutoZoom(false);
        $this->setContainerId('map_canvas');
        $this->setWidth(390);
        $this->setHeight(280);
        $this->setShowMapTypeControl(true);
        $this->setShowZoomControl(true);
    }
}
