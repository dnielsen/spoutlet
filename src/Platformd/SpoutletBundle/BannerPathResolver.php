<?php

namespace Platformd\SpoutletBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\SpoutletBundle\Entity\AbstractEvent;

/**
* Path resolver for the event banner
*/
class BannerPathResolver extends PathResolver
{

  /**
   * {@inheritDoc}
   */
  public function getPath($event, array $options)
  {
    /** @var $event \Platformd\SpoutletBundle\Entity\AbstractEvent */

    return parent::getPath($event->getBannerImage(), $options);
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {
   
    return $media instanceof AbstractEvent;
  }

}