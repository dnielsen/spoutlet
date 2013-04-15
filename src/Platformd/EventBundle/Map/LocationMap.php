<?php

namespace Platformd\EventBundle\Map;

use Vich\GeographicalBundle\Map\Map;

/**
 * LocationMap.
 */
class LocationMap extends Map
{
    /**
     * Constructs a new instance of LocationMap.
     */
    public function __construct()
    {
        parent::__construct();

        // configure your map in the constructor
        // by setting the options

        $this->setAutoZoom(false);
        $this->setContainerId('map_canvas');
        $this->setWidth(290);
        $this->setHeight(250);
        $this->setShowMapTypeControl(true);
        $this->setShowZoomControl(true);
    }
}
