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
  public function getPath($media, array $options)
  {

    return parent::getPath($media->getBanner(), $options);
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {
   
    return $media instanceof HomepageBanner;
  }
}