<?php

namespace Platformd\SpoutletBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\SpoutletBundle\Entity\AbstractEvent;

/**
* 
*/
class BannerPathResolver extends PathResolver
{

  /**
   * {@inheritDoc}
   */
  public function getPath($event, array $options)
  {

    return parent::getPath($media->getBanner(), $options);
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {
   
    return $media instanceof AbstractEvent;
  }

}