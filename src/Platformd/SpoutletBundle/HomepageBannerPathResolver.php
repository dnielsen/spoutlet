<?php

namespace Platformd\SpoutletBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\SpoutletBundle\Entity\HomepageBanner;

/**
*
*/
class HomepageBannerPathResolver extends PathResolver
{
   
  /**
   * {@inheritDoc}
   */
  public function getPath($banner, array $options)
  {
    $path = isset($options['size']) && $options['size'] === 'banner' ? $banner->getBanner() : $banner->getThumb();
    return parent::getPath($path,$options); 
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {
    return $media instanceof HomepageBanner;
  }
}
